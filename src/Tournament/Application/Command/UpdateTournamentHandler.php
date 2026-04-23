<?php
namespace App\Tournament\Application\Command;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;
final class UpdateTournamentHandler {
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(UpdateTournamentCommand $c): void {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->update($c->name, $c->startDate, $c->endDate, TournamentType::from($c->type), $c->maxParticipants, $c->description);
        $this->repo->save($t);
    }
}
