<?php
namespace App\Tests\Member\Domain;

use App\Member\Domain\Member;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MemberTest extends TestCase
{
    public function test_creation_with_required_fields(): void
    {
        $m = Member::create(Uuid::generate(), 'Dupont', 'Jean', PhoneNumber::fromString('0612345678'), null);
        self::assertSame('Dupont', $m->lastName());
        self::assertSame('Jean', $m->firstName());
        self::assertNull($m->email());
    }

    public function test_creation_with_email(): void
    {
        $m = Member::create(Uuid::generate(), 'D', 'J',
            PhoneNumber::fromString('0612345678'), Email::fromString('a@b.fr'));
        self::assertSame('a@b.fr', (string)$m->email());
    }

    public function test_update(): void
    {
        $m = Member::create(Uuid::generate(), 'A', 'B', PhoneNumber::fromString('0612345678'), null);
        $m->update('New', 'Name', PhoneNumber::fromString('0798765432'), Email::fromString('x@y.fr'));
        self::assertSame('New', $m->lastName());
        self::assertSame('+33798765432', (string)$m->phone());
    }
}
