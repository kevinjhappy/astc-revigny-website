<?php
namespace App\Tournament\Application\Command;
final class ReopenTournamentCommand {
    public function __construct(public readonly string $id) {}
}
