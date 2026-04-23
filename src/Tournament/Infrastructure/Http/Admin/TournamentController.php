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

    #[Route('/new', name: 'admin_tournament_new', methods: ['GET','POST'])]
    public function new(Request $r, CreateTournamentHandler $h): Response
    {
        $form = $this->createForm(TournamentType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new CreateTournamentCommand($d['name'], $d['startDate'], $d['endDate'],
                $d['type'], (int)$d['maxParticipants'], $d['description'] ?? null));
            $this->addFlash('success', 'Tournoi créé');
            return $this->redirectToRoute('admin_tournament_list');
        }
        return $this->render('admin/tournament/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_tournament_detail', methods: ['GET'])]
    public function detail(string $id, TournamentRepository $repo, RegistrationRepository $regRepo): Response
    {
        $t = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        return $this->render('admin/tournament/detail.html.twig', [
            'tournament' => $t,
            'registrations' => $regRepo->byTournament(Uuid::fromString($id)),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tournament_edit', methods: ['GET','POST'])]
    public function edit(string $id, Request $r, TournamentRepository $repo, UpdateTournamentHandler $h): Response
    {
        $t = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(TournamentType::class, [
            'name' => $t->name(), 'startDate' => $t->startDate(), 'endDate' => $t->endDate(),
            'type' => $t->type()->value, 'maxParticipants' => $t->maxParticipants(), 'description' => $t->description(),
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdateTournamentCommand($id, $d['name'], $d['startDate'], $d['endDate'],
                $d['type'], (int)$d['maxParticipants'], $d['description'] ?? null));
            $this->addFlash('success', 'Tournoi mis à jour');
            return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
        }
        return $this->render('admin/tournament/edit.html.twig', ['form' => $form, 'tournament' => $t]);
    }

    #[Route('/{id}/publish', name: 'admin_tournament_publish', methods: ['POST'])]
    public function publish(string $id, Request $r, PublishTournamentHandler $h): Response
    {
        if ($this->isCsrfTokenValid('pub'.$id, $r->request->get('_token'))) $h(new PublishTournamentCommand($id));
        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }

    #[Route('/{id}/close', name: 'admin_tournament_close', methods: ['POST'])]
    public function close(string $id, Request $r, CloseTournamentHandler $h): Response
    {
        if ($this->isCsrfTokenValid('cls'.$id, $r->request->get('_token'))) $h(new CloseTournamentCommand($id));
        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }
}
