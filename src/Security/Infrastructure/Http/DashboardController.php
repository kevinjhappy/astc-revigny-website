<?php

namespace App\Security\Infrastructure\Http;

use App\Member\Domain\MemberRepository;
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_root', methods: ['GET'])]
    public function root(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(
        TournamentRepository $tournamentRepository,
        MemberRepository $memberRepository,
        RegistrationRepository $registrationRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'activeTournaments' => count($tournamentRepository->published()),
            'totalMembers' => count($memberRepository->search(null)),
            'totalRegistrations' => count($registrationRepository->all(null, null)),
            'recentRegistrations' => array_slice($registrationRepository->all(null, null), 0, 10),
        ]);
    }
}
