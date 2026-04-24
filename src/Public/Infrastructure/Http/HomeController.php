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
        $galleryList = array_diff(scandir('./images/photos', SCANDIR_SORT_ASCENDING), ['..', '.']);
        $openTournaments = [];
        $closedTournaments = [];
        foreach ($tRepo->publishedOrClosed() as $t) {
            $row = [
                'id' => (string)$t->id(),
                'name' => $t->name(),
                'startDate' => $t->startDate(),
                'endDate' => $t->endDate(),
                'type' => $t->type()->value,
                'status' => $t->status()->value,
                'description' => $t->description(),
                'max' => $t->maxParticipants(),
                'confirmed' => $rRepo->countConfirmed($t->id()),
            ];
            if ($t->status()->value === 'PUBLISHED') {
                $openTournaments[] = $row;
            } else {
                $closedTournaments[] = $row;
            }
        }
        return $this->render('public/home.html.twig', [
            'tournaments' => $openTournaments,
            'closedTournaments' => $closedTournaments,
            'galleryList' => $galleryList,
        ]);
    }
}
