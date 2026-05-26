<?php
namespace App\Member\Application\Command;

final class StartNewSeasonCommand
{
    public function __construct(
        public readonly string $season,
    ) {}
}
