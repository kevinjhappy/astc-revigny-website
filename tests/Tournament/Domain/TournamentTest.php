<?php
namespace App\Tests\Tournament\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentStatus;
use App\Tournament\Domain\TournamentType;
use PHPUnit\Framework\TestCase;

final class TournamentTest extends TestCase
{
    private function make(): Tournament
    {
        return Tournament::create(Uuid::generate(), 'Open 2026',
            new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-03'),
            TournamentType::OPEN, 32, null);
    }

    public function test_starts_draft(): void
    {
        self::assertSame(TournamentStatus::DRAFT, $this->make()->status());
    }

    public function test_publish(): void
    {
        $t = $this->make(); $t->publish();
        self::assertSame(TournamentStatus::PUBLISHED, $t->status());
    }

    public function test_cannot_publish_if_end_before_start(): void
    {
        $this->expectException(\DomainException::class);
        Tournament::create(Uuid::generate(), 'x',
            new \DateTimeImmutable('2026-06-03'), new \DateTimeImmutable('2026-06-01'),
            TournamentType::OPEN, 10, null);
    }

    public function test_close_from_published(): void
    {
        $t = $this->make(); $t->publish(); $t->close();
        self::assertSame(TournamentStatus::CLOSED, $t->status());
    }

    public function test_cannot_close_draft(): void
    {
        $this->expectException(\DomainException::class);
        $this->make()->close();
    }

    public function test_max_participants_must_be_positive(): void
    {
        $this->expectException(\DomainException::class);
        Tournament::create(Uuid::generate(), 'x',
            new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-03'),
            TournamentType::OPEN, 0, null);
    }
}
