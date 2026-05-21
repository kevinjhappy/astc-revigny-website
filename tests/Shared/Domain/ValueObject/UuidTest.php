<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function test_generates_valid_v4(): void
    {
        $uuid = Uuid::generate();
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string)$uuid);
    }

    public function test_from_string_round_trip(): void
    {
        $uuidString = '11111111-1111-4111-8111-111111111111';
        self::assertSame($uuidString, (string)Uuid::fromString($uuidString));
    }

    public function test_rejects_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Uuid::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $uuidString = '11111111-1111-4111-8111-111111111111';
        self::assertTrue(Uuid::fromString($uuidString)->equals(Uuid::fromString($uuidString)));
    }
}
