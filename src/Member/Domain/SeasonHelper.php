<?php

declare(strict_types=1);

namespace App\Member\Domain;

final class SeasonHelper
{
    public function currentSeason(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? "$year-" . ($year + 1) : ($year - 1) . "-$year";
    }

    public function nextSeason(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? ($year + 1) . '-' . ($year + 2) : "$year-" . ($year + 1);
    }

    public function previousSeason(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? ($year - 1) . "-$year" : ($year - 2) . '-' . ($year - 1);
    }
}
