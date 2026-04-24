<?php
namespace App\Tournament\Application\Command;
final class CloseTournamentCommand { public function __construct(public readonly string $id) {} }
