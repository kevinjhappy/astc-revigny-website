<?php
namespace App\Tournament\Application\Command;
final class UpdateTournamentCommand {
    public function __construct(
        public readonly string $id, public readonly string $name,
        public readonly \DateTimeImmutable $startDate, public readonly \DateTimeImmutable $endDate,
        public readonly string $type, public readonly int $maxParticipants,
        public readonly ?string $description,
    ) {}
}
