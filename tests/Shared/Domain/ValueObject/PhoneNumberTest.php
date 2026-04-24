<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function test_parses_french_local(): void
    {
        self::assertSame('+33612345678', (string)PhoneNumber::fromString('0612345678'));
    }

    public function test_parses_international(): void
    {
        self::assertSame('+33612345678', (string)PhoneNumber::fromString('+33 6 12 34 56 78'));
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PhoneNumber::fromString('abc');
    }
}
