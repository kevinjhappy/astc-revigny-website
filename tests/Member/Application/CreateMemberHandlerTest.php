<?php

namespace App\Tests\Member\Application;

use App\Member\Application\Command\CreateMemberCommand;
use App\Member\Application\Command\CreateMemberHandler;
use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CreateMemberHandlerTest extends TestCase
{
    public function test_creates_and_persists(): void
    {
        $repo = new class implements MemberRepository {
            public array $store = [];
            public function save(Member $member): void { $this->store[(string)$member->id()] = $member; }
            public function remove(Member $member): void { unset($this->store[(string)$member->id()]); }
            public function get(Uuid $id): ?Member { return $this->store[(string)$id] ?? null; }
            public function search(?string $query): array { return array_values($this->store); }
            public function findByLastNameAndPhone(string $lastName, PhoneNumber $phoneNumber): ?Member { return null; }
        };
        $id = (new CreateMemberHandler($repo))(new CreateMemberCommand('Dupont', 'Jean', '0612345678', null));
        self::assertCount(1, $repo->store);
        self::assertSame('Dupont', $repo->get($id)->lastName());
    }
}
