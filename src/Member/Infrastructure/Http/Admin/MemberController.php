<?php
namespace App\Member\Infrastructure\Http\Admin;

use App\Member\Application\Command\CreateMemberCommand;
use App\Member\Application\Command\CreateMemberHandler;
use App\Member\Application\Command\CreateMemberSubscriptionCommand;
use App\Member\Application\Command\CreateMemberSubscriptionHandler;
use App\Member\Application\Command\DeleteMemberCommand;
use App\Member\Application\Command\DeleteMemberHandler;
use App\Member\Application\Command\StartNewSeasonCommand;
use App\Member\Application\Command\StartNewSeasonHandler;
use App\Member\Application\Command\UpdateMemberCommand;
use App\Member\Application\Command\UpdateMemberHandler;
use App\Member\Application\Command\UpdateMemberSubscriptionCommand;
use App\Member\Application\Command\UpdateMemberSubscriptionHandler;
use App\Member\Domain\MemberRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Member\Infrastructure\Http\Admin\Form\MemberType;
use App\Shared\Domain\ValueObject\Uuid;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/admin/members")]
#[IsGranted("ROLE_ADMIN")]
final class MemberController extends AbstractController
{
    #[Route("", name: "admin_member_list", methods: ["GET"])]
    public function list(
        Request $r,
        MemberRepository $repo,
        MemberSubscriptionRepository $subRepo,
        SeasonHelper $seasonHelper,
    ): Response {
        $q = $r->query->get('q', '');
        $members = $repo->search($q ?: null);
        $season = $seasonHelper->currentSeason();
        $subsBySeason = [];
        foreach ($subRepo->findBySeason($season) as $sub) {
            $subsBySeason[$sub->memberId()] = $sub;
        }
        $rows = array_map(fn($m) => [
            'member' => $m,
            'subscription' => $subsBySeason[(string)$m->id()] ?? null,
        ], $members);

        $nextSeason = $seasonHelper->nextSeason();
        $showSeasonButton = !$subRepo->hasAnySeason($nextSeason);

        return $this->render('admin/member/list.html.twig', [
            'rows' => $rows,
            'q' => $q,
            'currentSeason' => $season,
            'nextSeason' => $nextSeason,
            'showSeasonButton' => $showSeasonButton,
        ]);
    }

    #[Route("/new", name: "admin_member_new", methods: ["GET", "POST"])]
    public function new(
        Request $r,
        CreateMemberHandler $h,
        CreateMemberSubscriptionHandler $subHandler,
        SeasonHelper $seasonHelper,
    ): Response {
        $form = $this->createForm(MemberType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $memberId = $h(new CreateMemberCommand($d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null, $d['birthDate'] ?? null));
            if ($d['membershipType'] !== null) {
                $subHandler(new CreateMemberSubscriptionCommand(
                    (string)$memberId,
                    $seasonHelper->currentSeason(),
                    $d['membershipType'],
                    $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING,
                ));
            }
            $this->addFlash('success', 'Membre créé');
            return $this->redirectToRoute('admin_member_list');
        }
        return $this->render('admin/member/new.html.twig', ['form' => $form]);
    }

    #[Route("/start-season", name: "admin_member_start_season", methods: ["POST"])]
    public function startSeason(Request $r, StartNewSeasonHandler $h): Response
    {
        $season = $r->request->get('season', '');
        if ($this->isCsrfTokenValid('start-season', $r->request->get('_token'))) {
            ($h)(new StartNewSeasonCommand($season));
            $this->addFlash('success', "Saison $season démarrée. Les membres PAYÉS de la saison précédente sont passés en « En attente ».");
        }
        return $this->redirectToRoute('admin_member_list');
    }

    #[Route("/{id}/edit", name: "admin_member_edit", methods: ["GET", "POST"])]
    public function edit(
        string $id,
        Request $r,
        MemberRepository $repo,
        UpdateMemberHandler $h,
        CreateMemberSubscriptionHandler $createSubHandler,
        UpdateMemberSubscriptionHandler $updateSubHandler,
        MemberSubscriptionRepository $subRepo,
        SeasonHelper $seasonHelper,
    ): Response {
        $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $currentSub = $subRepo->findByMemberAndSeason((string)$m->id(), $seasonHelper->currentSeason());
        $form = $this->createForm(MemberType::class, [
            'lastName' => $m->lastName(),
            'firstName' => $m->firstName(),
            'phone' => (string)$m->phone(),
            'email' => $m->email() ? (string)$m->email() : null,
            'birthDate' => $m->birthDate()?->format('d/m/Y'),
            'membershipType' => $currentSub?->type(),
            'subscriptionStatus' => $currentSub?->status(),
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdateMemberCommand($id, $d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null, $d['birthDate'] ?? null));
            if ($d['membershipType'] !== null) {
                if ($currentSub !== null) {
                    $updateSubHandler(new UpdateMemberSubscriptionCommand(
                        (string)$currentSub->id(),
                        $d['membershipType'],
                        $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING,
                    ));
                } else {
                    $createSubHandler(new CreateMemberSubscriptionCommand(
                        (string)$m->id(),
                        $seasonHelper->currentSeason(),
                        $d['membershipType'],
                        $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING,
                    ));
                }
            }
            $this->addFlash('success', 'Membre mis à jour');
            return $this->redirectToRoute('admin_member_list');
        }
        return $this->render('admin/member/edit.html.twig', ['form' => $form, 'member' => $m]);
    }

    #[Route("/import", name: "admin_member_import", methods: ["GET", "POST"])]
    public function import(
        Request $r,
        CreateMemberHandler $h,
        UpdateMemberHandler $updateHandler,
        CreateMemberSubscriptionHandler $createSubHandler,
        UpdateMemberSubscriptionHandler $updateSubHandler,
        MemberRepository $repo,
        MemberSubscriptionRepository $subRepo,
        SeasonHelper $seasonHelper,
    ): Response {
        if ($r->getMethod() === 'GET') {
            return $this->render('admin/member/import.html.twig');
        }

        if (!$this->isCsrfTokenValid('member-import', $r->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_member_import');
        }

        $file = $r->files->get('csv');
        if (!$file) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('admin_member_import');
        }

        $handle = fopen($file->getPathname(), 'r');
        $imported = 0;
        $line = 0;

        $headers = array_map('trim', fgetcsv($handle, 0, ';') ?: []);
        $isNewFormat = isset($headers[0]) && ($headers[0] === 'id' || $headers[0] === 'Nom');

        if ($isNewFormat) {
            $colIndex = array_flip($headers);
            $season = $seasonHelper->currentSeason();

            while (($raw = fgetcsv($handle, 0, ';')) !== false) {
                $line++;
                $row = array_map('trim', $raw);

                $idVal       = $row[$colIndex['id'] ?? -1] ?? '';
                $lastName    = $row[$colIndex['Nom'] ?? -1] ?? '';
                $firstName   = $row[$colIndex['Prénom'] ?? -1] ?? '';
                $phone       = $row[$colIndex['Téléphone'] ?? -1] ?? '';
                $email       = $row[$colIndex['Email'] ?? -1] ?? '';
                $birthDate   = $row[$colIndex['Date de naissance'] ?? -1] ?? '';
                $typeRaw     = $row[$colIndex['Type de cotisation'] ?? -1] ?? '';
                $statusRaw   = $row[$colIndex['Statut paiement'] ?? -1] ?? '';

                if ($lastName === '' || $phone === '') {
                    $this->addFlash('error', "Ligne $line : nom ou téléphone manquant, ignorée.");
                    continue;
                }

                $member = null;
                if ($idVal !== '') {
                    try {
                        $member = $repo->get(Uuid::fromString($idVal));
                    } catch (\Throwable) {
                        $this->addFlash('error', "Ligne $line : UUID invalide « $idVal », ignorée.");
                        continue;
                    }
                }
                if ($member === null) {
                    $member = $repo->findByLastNameAndPhone($lastName, $phone);
                }

                try {
                    if ($member !== null) {
                        $updateHandler(new UpdateMemberCommand(
                            (string)$member->id(),
                            $lastName,
                            $firstName !== '' ? $firstName : $member->firstName(),
                            $phone,
                            $email !== '' ? $email : ($member->email() ? (string)$member->email() : null),
                            $birthDate !== '' ? $birthDate : $member->birthDate()?->format('d/m/Y'),
                        ));
                        $memberId = (string)$member->id();
                    } else {
                        $newId = $h(new CreateMemberCommand($lastName, $firstName, $phone, $email ?: null, $birthDate ?: null));
                        $memberId = (string)$newId;
                    }
                    $imported++;
                } catch (\Throwable $e) {
                    $this->addFlash('error', "Ligne $line ($firstName $lastName) : " . $e->getMessage());
                    continue;
                }

                if ($typeRaw === '' || $typeRaw === '—') {
                    continue;
                }

                $membershipType = MembershipType::tryFrom($typeRaw);
                if ($membershipType === null) {
                    foreach (MembershipType::cases() as $case) {
                        if ($case->label() === $typeRaw) {
                            $membershipType = $case;
                            break;
                        }
                    }
                }
                if ($membershipType === null) {
                    $this->addFlash('error', "Ligne $line : type de cotisation inconnu « $typeRaw », abonnement ignoré.");
                    continue;
                }

                $status = SubscriptionStatus::tryFrom($statusRaw);
                if ($status === null) {
                    foreach (SubscriptionStatus::cases() as $case) {
                        if ($case->label() === $statusRaw) {
                            $status = $case;
                            break;
                        }
                    }
                }
                $status ??= SubscriptionStatus::PENDING;

                try {
                    $existingSub = $subRepo->findByMemberAndSeason($memberId, $season);
                    if ($existingSub !== null) {
                        $updateSubHandler(new UpdateMemberSubscriptionCommand((string)$existingSub->id(), $membershipType, $status));
                    } else {
                        $createSubHandler(new CreateMemberSubscriptionCommand($memberId, $season, $membershipType, $status));
                    }
                } catch (\Throwable $e) {
                    $this->addFlash('error', "Ligne $line ($firstName $lastName) abonnement : " . $e->getMessage());
                }
            }
        } else {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $line++;
                if (count($row) < 3) {
                    $this->addFlash('error', "Ligne $line : colonnes insuffisantes, ignorée.");
                    continue;
                }

                [$firstName, $lastName, $phone] = array_map('trim', $row);
                $email     = isset($row[3]) ? trim($row[3]) : null;
                $birthDate = isset($row[4]) ? trim($row[4]) : null;

                if ($firstName === '' || $lastName === '' || $phone === '') {
                    $this->addFlash('error', "Ligne $line : prénom, nom ou téléphone manquant, ignorée.");
                    continue;
                }

                try {
                    $h(new CreateMemberCommand($lastName, $firstName, $phone, $email ?: null, $birthDate ?: null));
                    $imported++;
                } catch (\Throwable $e) {
                    $this->addFlash('error', "Ligne $line ($firstName $lastName) : " . $e->getMessage());
                }
            }
        }

        fclose($handle);

        if ($imported > 0) {
            $this->addFlash('success', "$imported membre(s) importé(s) avec succès.");
        }

        return $this->redirectToRoute('admin_member_list');
    }

    #[Route("/export", name: "admin_member_export", methods: ["GET"])]
    public function export(
        MemberRepository $repo,
        MemberSubscriptionRepository $subRepo,
        SeasonHelper $seasonHelper,
    ): Response {
        $season = $seasonHelper->currentSeason();
        $members = $repo->search(null);
        $subsBySeason = [];
        foreach ($subRepo->findBySeason($season) as $sub) {
            $subsBySeason[$sub->memberId()] = $sub;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['id', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Date de naissance', 'Type de cotisation', 'Statut paiement', 'Saison'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $row = 2;
        foreach ($members as $m) {
            $sub = $subsBySeason[(string)$m->id()] ?? null;
            $sheet->fromArray([[
                (string)$m->id(),
                $m->lastName(),
                $m->firstName(),
                (string)$m->phone(),
                $m->email() ? (string)$m->email() : '',
                $m->birthDate()?->format('d/m/Y') ?? '',
                $sub ? $sub->type()->label() : '—',
                $sub ? $sub->status()->label() : '—',
                $sub ? $sub->season() : '—',
            ]], null, 'A'.$row);
            $row++;
        }

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'membres-'.$season.'.xlsx'));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route("/{id}", name: "admin_member_show", methods: ["GET"])]
    public function show(
        string $id,
        MemberRepository $repo,
        MemberSubscriptionRepository $subRepo,
    ): Response {
        $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        return $this->render('admin/member/show.html.twig', [
            'member' => $m,
            'subscriptions' => $subRepo->findByMember((string)$m->id()),
        ]);
    }

    #[Route("/{id}", name: "admin_member_delete", methods: ["POST"])]
    public function delete(string $id, Request $r, DeleteMemberHandler $h): Response
    {
        if ($this->isCsrfTokenValid('del'.$id, $r->request->get('_token'))) {
            $h(new DeleteMemberCommand($id));
            $this->addFlash('success', 'Membre supprimé');
        }
        return $this->redirectToRoute('admin_member_list');
    }
}
