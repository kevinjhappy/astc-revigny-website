<?php

namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;

final class CloseTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}

    public function __invoke(CloseTournamentCommand $command): void
    {
        $tournament = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('not found');
        $tournament->close();
        $this->repo->save($tournament);
    }
}
