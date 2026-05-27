<?php

namespace App\Registration\Infrastructure\Http\Admin;

use App\Registration\Application\Command\CancelRegistrationCommand;
use App\Registration\Application\Command\CancelRegistrationHandler;
use App\Registration\Application\Command\ConfirmRegistrationCommand;
use App\Registration\Application\Command\ConfirmRegistrationHandler;
use App\Registration\Application\Command\DeleteRegistrationCommand;
use App\Registration\Application\Command\DeleteRegistrationHandler;
use App\Registration\Application\Command\ResetRegistrationCommand;
use App\Registration\Application\Command\ResetRegistrationHandler;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/registrations')]
#[IsGranted('ROLE_ADMIN')]
final class RegistrationController extends AbstractController
{
    private const EXPORT_STATUSES = [
        RegistrationStatus::CONFIRMED,
        RegistrationStatus::PENDING,
        RegistrationStatus::WAITING_LIST,
    ];

    #[Route('/export', name: 'admin_registration_export', methods: ['GET'])]
    public function export(Request $request, RegistrationRepository $repo, TournamentRepository $tournamentRepository): Response
    {
        $tournamentParam = $request->query->get('tournament', 'all');
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        if ($tournamentParam !== 'all' && $tournamentParam !== '') {
            $tournament = $tournamentRepository->get(Uuid::fromString($tournamentParam));
            if ($tournament === null || $tournament->status() !== TournamentStatus::PUBLISHED) {
                throw $this->createNotFoundException('Tournoi introuvable ou non ouvert.');
            }
            $registrations = array_values(array_filter(
                $repo->byTournament($tournament->id()),
                fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
            ));
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($tournament->name(), 0, 31));
            $this->fillSheet($sheet, $registrations);
            $filename = 'inscriptions-' . preg_replace('/[^a-z0-9-]/i', '-', $tournament->name()) . '.xlsx';
        } else {
            $tournaments = $tournamentRepository->published();
            if (empty($tournaments)) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Aucun tournoi');
                $sheet->setCellValue('A1', 'Aucun tournoi ouvert.');
            }
            foreach ($tournaments as $tournament) {
                $registrations = array_values(array_filter(
                    $repo->byTournament($tournament->id()),
                    fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
                ));
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle(mb_substr($tournament->name(), 0, 31));
                $this->fillSheet($sheet, $registrations);
            }
            $filename = 'inscriptions-' . (new \DateTimeImmutable())->format('Y-m-d') . '.xlsx';
        }

        $spreadsheet->setActiveSheetIndex(0);

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /** @param Registration[] $registrations */
    private function fillSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $registrations): void
    {
        $headers = ['Nom', 'Prénom', 'Téléphone', 'Email', 'Statut', 'Date d\'inscription'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($registrations as $r) {
            $sheet->fromArray([[
                $r->lastName(),
                $r->firstName(),
                (string) $r->phone(),
                $r->email() ? (string) $r->email() : '',
                $r->status()->value,
                $r->registeredAt()->format('d/m/Y H:i'),
            ]], null, 'A' . $rowIndex);
            $rowIndex++;
        }
    }

    #[Route('', name: 'admin_registration_list', methods: ['GET'])]
    public function list(Request $request, RegistrationRepository $repo, TournamentRepository $tournamentRepository): Response
    {
        $activeTournaments = $tournamentRepository->notClosed();
        $activeTournamentIds = array_map(fn($tournament) => (string)$tournament->id(), $activeTournaments);

        return $this->render('admin/registration/list.html.twig', [
            'registrations' => $repo->all($request->query->get('tournament'), $request->query->get('status'), $activeTournamentIds),
            'tournaments' => $activeTournaments,
            'selectedTournament' => $request->query->get('tournament'),
            'selectedStatus' => $request->query->get('status'),
        ]);
    }

    #[Route('/{id}/confirm', name: 'admin_registration_confirm', methods: ['POST'])]
    public function confirm(string $id, Request $request, ConfirmRegistrationHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('cnf'.$id, $request->request->get('_token'))) {
            $handler(new ConfirmRegistrationCommand($id));
        }

        return $this->redirectToRoute('admin_registration_list');
    }

    #[Route('/{id}/cancel', name: 'admin_registration_cancel', methods: ['POST'])]
    public function cancel(string $id, Request $request, CancelRegistrationHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('can'.$id, $request->request->get('_token'))) {
            $handler(new CancelRegistrationCommand($id));
        }

        return $this->redirectToRoute('admin_registration_list');
    }

    #[Route('/{id}/reset', name: 'admin_registration_reset', methods: ['POST'])]
    public function reset(string $id, Request $request, ResetRegistrationHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('rst'.$id, $request->request->get('_token'))) {
            $handler(new ResetRegistrationCommand($id));
        }

        return $this->redirectToRoute('admin_registration_list');
    }

    #[Route('/{id}', name: 'admin_registration_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, DeleteRegistrationHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('del'.$id, $request->request->get('_token'))) {
            $handler(new DeleteRegistrationCommand($id));
        }

        return $this->redirectToRoute('admin_registration_list');
    }
}
