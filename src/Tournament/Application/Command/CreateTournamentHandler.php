<?php
namespace App\Tournament\Application\Command;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;
final class CreateTournamentHandler {
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(CreateTournamentCommand $c): Uuid {
        $id = Uuid::generate();
        $this->repo->save(Tournament::create($id, $c->name, $c->startDate, $c->endDate,
            TournamentType::from($c->type), $c->maxParticipants, $c->description));
        return $id;
    }
}
