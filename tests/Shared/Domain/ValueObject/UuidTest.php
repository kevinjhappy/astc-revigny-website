<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function test_generates_valid_v4(): void
    {
        $u = Uuid::generate();
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string)$u);
    }

    public function test_from_string_round_trip(): void
    {
        $s = '11111111-1111-4111-8111-111111111111';
        self::assertSame($s, (string)Uuid::fromString($s));
    }

    public function test_rejects_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Uuid::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $s = '11111111-1111-4111-8111-111111111111';
        self::assertTrue(Uuid::fromString($s)->equals(Uuid::fromString($s)));
    }
}
