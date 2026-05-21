<?php

namespace App\Public\Infrastructure\Http;

use App\News\Domain\PostRepository;
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(private int $newsCount) {}

    #[Route('/', name: 'public_home', methods: ['GET'])]
    public function home(TournamentRepository $tournamentRepository, RegistrationRepository $registrationRepository, PostRepository $postRepo): Response
    {
        $galleryList = array_diff(scandir('./images/photos', SCANDIR_SORT_ASCENDING), ['..', '.']);
        $openTournaments = [];
        $closedTournaments = [];
        foreach ($tournamentRepository->publishedOrClosed() as $tournament) {
            $row = [
                'id' => (string)$tournament->id(),
                'name' => $tournament->name(),
                'startDate' => $tournament->startDate(),
                'endDate' => $tournament->endDate(),
                'type' => $tournament->type()->value,
                'status' => $tournament->status()->value,
                'description' => $tournament->description(),
                'max' => $tournament->maxParticipants(),
                'confirmed' => $registrationRepository->countConfirmed($tournament->id()),
            ];
            if ($tournament->status()->value === 'PUBLISHED') {
                $openTournaments[] = $row;
            } else {
                $closedTournaments[] = $row;
            }
        }

        return $this->render('public/home.html.twig', [
            'tournaments' => $openTournaments,
            'closedTournaments' => $closedTournaments,
            'galleryList' => $galleryList,
            'newsPosts' => $postRepo->latestPublished($this->newsCount),
        ]);
    }
}
