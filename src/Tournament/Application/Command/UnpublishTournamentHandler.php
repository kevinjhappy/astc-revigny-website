<?php

namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;

final class UnpublishTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}

    public function __invoke(UnpublishTournamentCommand $command): void
    {
        $tournament = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('not found');
        $tournament->unpublish();
        $this->repo->save($tournament);
    }
}
