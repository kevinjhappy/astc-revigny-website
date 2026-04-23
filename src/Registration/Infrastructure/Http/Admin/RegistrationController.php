<?php
namespace App\Registration\Infrastructure\Http\Admin;
use App\Registration\Application\Command\CancelRegistrationCommand;
use App\Registration\Application\Command\CancelRegistrationHandler;
use App\Registration\Application\Command\ConfirmRegistrationCommand;
use App\Registration\Application\Command\ConfirmRegistrationHandler;
use App\Registration\Application\Command\DeleteRegistrationCommand;
use App\Registration\Application\Command\DeleteRegistrationHandler;
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
    public function list(Request $r, RegistrationRepository $repo, TournamentRepository $trepo): Response
    {
        return $this->render('admin/registration/list.html.twig', [
            'registrations' => $repo->all($r->query->get('tournament'), $r->query->get('status')),
            'tournaments' => $trepo->all(),
            'selectedTournament' => $r->query->get('tournament'),
            'selectedStatus' => $r->query->get('status'),
        ]);
    }
    #[Route('/{id}/confirm', name: 'admin_registration_confirm', methods: ['POST'])]
    public function confirm(string $id, Request $r, ConfirmRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('cnf'.$id, $r->request->get('_token'))) $h(new ConfirmRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }
    #[Route('/{id}/cancel', name: 'admin_registration_cancel', methods: ['POST'])]
    public function cancel(string $id, Request $r, CancelRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('can'.$id, $r->request->get('_token'))) $h(new CancelRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }
    #[Route('/{id}', name: 'admin_registration_delete', methods: ['POST'])]
    public function delete(string $id, Request $r, DeleteRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('del'.$id, $r->request->get('_token'))) $h(new DeleteRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }
}
