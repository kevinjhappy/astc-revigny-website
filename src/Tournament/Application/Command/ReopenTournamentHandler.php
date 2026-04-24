<?php
namespace App\Tournament\Application\Command;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
final class ReopenTournamentHandler {
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(ReopenTournamentCommand $c): void {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->reopen();
        $this->repo->save($t);
    }
}
