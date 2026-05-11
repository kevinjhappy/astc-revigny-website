<?php

declare(strict_types=1);

namespace App\Member\Application\Command;

final class StartNewSeasonCommand
{
    public function __construct(public readonly string $season)
    {
        if (!preg_match('/^\d{4}-\d{4}$/', $season)) {
            throw new \InvalidArgumentException("Format de saison invalide : $season. Attendu : YYYY-YYYY (ex. 2025-2026).");
        }
    }
}
