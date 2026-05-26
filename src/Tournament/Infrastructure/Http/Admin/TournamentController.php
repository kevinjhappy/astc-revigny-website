<?php

namespace App\Tournament\Infrastructure\Http\Admin;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Application\Command\CloseTournamentCommand;
use App\Tournament\Application\Command\CloseTournamentHandler;
use App\Tournament\Application\Command\CreateTournamentCommand;
use App\Tournament\Application\Command\CreateTournamentHandler;
use App\Tournament\Application\Command\PublishTournamentCommand;
use App\Tournament\Application\Command\PublishTournamentHandler;
use App\Tournament\Application\Command\ReopenTournamentCommand;
use App\Tournament\Application\Command\ReopenTournamentHandler;
use App\Tournament\Application\Command\UnpublishTournamentCommand;
use App\Tournament\Application\Command\UnpublishTournamentHandler;
use App\Tournament\Application\Command\UpdateTournamentCommand;
use App\Tournament\Application\Command\UpdateTournamentHandler;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Infrastructure\Http\Admin\Form\TournamentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournaments')]
#[IsGranted('ROLE_ADMIN')]
final class TournamentController extends AbstractController
{
    #[Route('', name: 'admin_tournament_list', methods: ['GET'])]
    public function list(TournamentRepository $repo): Response
    {
        return $this->render('admin/tournament/list.html.twig', ['tournaments' => $repo->all()]);
    }

    #[Route('/new', name: 'admin_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CreateTournamentHandler $handler): Response
    {
        $form = $this->createForm(TournamentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $handler(new CreateTournamentCommand($formData['name'], $formData['startDate'], $formData['endDate'],
                $formData['type'], (int)$formData['maxParticipants'], $formData['description'] ?? null));
            $this->addFlash('success', 'Tournoi créé');

            return $this->redirectToRoute('admin_tournament_list');
        }

        return $this->render('admin/tournament/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_tournament_detail', methods: ['GET'])]
    public function detail(string $id, TournamentRepository $repo, RegistrationRepository $regRepo): Response
    {
        $tournament = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();

        return $this->render('admin/tournament/detail.html.twig', [
            'tournament' => $tournament,
            'registrations' => $regRepo->byTournament(Uuid::fromString($id)),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, TournamentRepository $repo, UpdateTournamentHandler $handler): Response
    {
        $tournament = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(TournamentType::class, [
            'name' => $tournament->name(), 'startDate' => $tournament->startDate(), 'endDate' => $tournament->endDate(),
            'type' => $tournament->type()->value, 'maxParticipants' => $tournament->maxParticipants(), 'description' => $tournament->description(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $handler(new UpdateTournamentCommand($id, $formData['name'], $formData['startDate'], $formData['endDate'],
                $formData['type'], (int)$formData['maxParticipants'], $formData['description'] ?? null));
            $this->addFlash('success', 'Tournoi mis à jour');

            return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
        }

        return $this->render('admin/tournament/edit.html.twig', ['form' => $form, 'tournament' => $tournament]);
    }

    #[Route('/{id}/publish', name: 'admin_tournament_publish', methods: ['POST'])]
    public function publish(string $id, Request $request, PublishTournamentHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('pub'.$id, $request->request->get('_token'))) {
            $handler(new PublishTournamentCommand($id));
            $this->addFlash('success', 'Tournoi publié');
        }

        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }

    #[Route('/{id}/close', name: 'admin_tournament_close', methods: ['POST'])]
    public function close(string $id, Request $request, CloseTournamentHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('cls'.$id, $request->request->get('_token'))) {
            $handler(new CloseTournamentCommand($id));
            $this->addFlash('success', 'Tournoi clôturé');
        }

        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }

    #[Route('/{id}/reopen', name: 'admin_tournament_reopen', methods: ['POST'])]
    public function reopen(string $id, Request $request, ReopenTournamentHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('rop'.$id, $request->request->get('_token'))) {
            $handler(new ReopenTournamentCommand($id));
            $this->addFlash('success', 'Tournoi réouvert');
        }

        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }

    #[Route('/{id}/unpublish', name: 'admin_tournament_unpublish', methods: ['POST'])]
    public function unpublish(string $id, Request $request, UnpublishTournamentHandler $handler): Response
    {
        if ($this->isCsrfTokenValid('unp'.$id, $request->request->get('_token'))) {
            $handler(new UnpublishTournamentCommand($id));
            $this->addFlash('success', 'Tournoi dépublié (brouillon)');
        }

        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }
}
