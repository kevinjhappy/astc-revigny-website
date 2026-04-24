<?php
namespace App\Registration\Domain;
use App\Shared\Domain\ValueObject\Uuid;
interface RegistrationRepository
{
    public function save(Registration $r): void;
    public function remove(Registration $r): void;
    public function get(Uuid $id): ?Registration;
    /** @return Registration[] */
    public function byTournament(Uuid $tournamentId): array;
    public function countConfirmed(Uuid $tournamentId): int;
    public function firstWaitingList(Uuid $tournamentId): ?Registration;
    /** @return Registration[] */
    public function all(?string $tournamentId, ?string $status): array;
}
