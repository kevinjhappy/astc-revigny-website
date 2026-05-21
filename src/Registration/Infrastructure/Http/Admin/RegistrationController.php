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
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/registrations')]
#[IsGranted('ROLE_ADMIN')]
final class RegistrationController extends AbstractController
{
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
