<?php
namespace App\Tournament\Application\Command;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
final class UnpublishTournamentHandler {
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(UnpublishTournamentCommand $c): void {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->unpublish();
        $this->repo->save($t);
    }
}
