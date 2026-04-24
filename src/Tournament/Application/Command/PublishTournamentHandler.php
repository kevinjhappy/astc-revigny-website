<?php
namespace App\Tournament\Application\Command;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
final class PublishTournamentHandler {
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(PublishTournamentCommand $c): void {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->publish(); $this->repo->save($t);
    }
}
