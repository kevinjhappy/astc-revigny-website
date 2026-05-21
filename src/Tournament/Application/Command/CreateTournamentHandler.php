<?php

namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;

final class CreateTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}

    public function __invoke(CreateTournamentCommand $command): Uuid
    {
        $id = Uuid::generate();
        $this->repo->save(Tournament::create($id, $command->name, $command->startDate, $command->endDate,
            TournamentType::from($command->type), $command->maxParticipants, $command->description));

        return $id;
    }
}
