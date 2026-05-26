<?php

namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;

final class UpdateTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}

    public function __invoke(UpdateTournamentCommand $command): void
    {
        $tournament = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('not found');
        $tournament->update($command->name, $command->startDate, $command->endDate, TournamentType::from($command->type), $command->maxParticipants, $command->description);
        $this->repo->save($tournament);
    }
}
