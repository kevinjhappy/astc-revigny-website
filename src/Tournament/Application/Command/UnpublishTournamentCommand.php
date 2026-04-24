<?php
namespace App\Tournament\Application\Command;
final class UnpublishTournamentCommand {
    public function __construct(public readonly string $id) {}
}
