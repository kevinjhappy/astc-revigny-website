<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_accepts_valid_email(): void
    {
        self::assertSame('a@b.fr', (string)Email::fromString('a@b.fr'));
    }

    public function test_normalizes_to_lowercase(): void
    {
        self::assertSame('a@b.fr', (string)Email::fromString('A@B.FR'));
    }

    public function test_rejects_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Email::fromString('not-an-email');
    }
}
