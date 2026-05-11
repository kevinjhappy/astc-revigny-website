<?php
namespace App\Tests\Member\Domain;

use App\Member\Domain\SeasonHelper;
use PHPUnit\Framework\TestCase;

final class SeasonHelperTest extends TestCase
{
    private SeasonHelper $h;
    protected function setUp(): void { $this->h = new SeasonHelper(); }

    public function test_current_season_before_september(): void
    {
        self::assertSame('2025-2026', $this->h->currentSeason(new \DateTimeImmutable('2026-08-31')));
    }

    public function test_current_season_from_september(): void
    {
        self::assertSame('2026-2027', $this->h->currentSeason(new \DateTimeImmutable('2026-09-01')));
    }

    public function test_next_season_before_september(): void
    {
        self::assertSame('2026-2027', $this->h->nextSeason(new \DateTimeImmutable('2026-05-01')));
    }

    public function test_next_season_from_september(): void
    {
        self::assertSame('2027-2028', $this->h->nextSeason(new \DateTimeImmutable('2026-09-01')));
    }

    public function test_previous_season(): void
    {
        self::assertSame('2024-2025', $this->h->previousSeason(new \DateTimeImmutable('2026-05-01')));
    }
}
