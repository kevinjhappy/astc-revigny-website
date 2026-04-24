<?php
namespace App\Tournament\Domain;
use App\Shared\Domain\ValueObject\Uuid;
interface TournamentRepository
{
    public function save(Tournament $t): void;
    public function get(Uuid $id): ?Tournament;
    /** @return Tournament[] */
    public function all(): array;
    /** @return Tournament[] */
    public function published(): array;
    /** @return Tournament[] */
    public function publishedOrClosed(): array;
}
