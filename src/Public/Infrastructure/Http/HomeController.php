<?php
namespace App\Public\Infrastructure\Http;
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class HomeController extends AbstractController
{
    #[Route('/', name: 'public_home', methods: ['GET'])]
    public function home(TournamentRepository $tRepo, RegistrationRepository $rRepo): Response
    {
        $tournaments = $tRepo->published();
        $rows = [];
        foreach ($tournaments as $t) {
            $rows[] = [
                'id' => (string)$t->id(),
                'name' => $t->name(),
                'startDate' => $t->startDate(),
                'endDate' => $t->endDate(),
                'type' => $t->type()->value,
                'description' => $t->description(),
                'max' => $t->maxParticipants(),
                'confirmed' => $rRepo->countConfirmed($t->id()),
            ];
        }
        return $this->render('public/home.html.twig', ['tournaments' => $rows]);
    }
}
