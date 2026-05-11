<?php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\UpdateMemberSubscriptionCommand;
use App\Member\Application\Command\UpdateMemberSubscriptionHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class UpdateMemberSubscriptionHandlerTest extends TestCase
{
    public function test_updates_type_and_status(): void
    {
        $sub = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN);
        $repo = new class($sub) implements MemberSubscriptionRepository {
            public array $store;
            public function __construct(MemberSubscription $s) { $this->store[(string)$s->id()] = $s; }
            public function save(MemberSubscription $s): void { $this->store[(string)$s->id()] = $s; }
            public function remove(MemberSubscription $s): void { unset($this->store[(string)$s->id()]); }
            public function get(Uuid $id): ?MemberSubscription { return $this->store[(string)$id] ?? null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return null; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
        $handler = new UpdateMemberSubscriptionHandler($repo);
        ($handler)(new UpdateMemberSubscriptionCommand((string)$sub->id(), MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID));
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }

    public function test_throws_if_not_found(): void
    {
        $repo = new class implements MemberSubscriptionRepository {
            public function save(MemberSubscription $s): void {}
            public function remove(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return null; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
        $handler = new UpdateMemberSubscriptionHandler($repo);
        $this->expectException(\DomainException::class);
        ($handler)(new UpdateMemberSubscriptionCommand(Uuid::generate()->__toString(), MembershipType::TERRAIN, SubscriptionStatus::PENDING));
    }
}
