<?php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\CreateMemberSubscriptionCommand;
use App\Member\Application\Command\CreateMemberSubscriptionHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CreateMemberSubscriptionHandlerTest extends TestCase
{
    private function makeRepo(): MemberSubscriptionRepository
    {
        return new class implements MemberSubscriptionRepository {
            public array $store = [];
            public function save(MemberSubscription $s): void { $this->store[(string)$s->id()] = $s; }
            public function remove(MemberSubscription $s): void { unset($this->store[(string)$s->id()]); }
            public function get(Uuid $id): ?MemberSubscription { return $this->store[(string)$id] ?? null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription {
                foreach ($this->store as $sub) {
                    if ($sub->memberId() === $m && $sub->season() === $s) return $sub;
                }
                return null;
            }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
    }

    public function test_creates_pending_subscription(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN_TOURNOIS));
        $sub = $repo->findByMemberAndSeason('m1', '2025-2026');
        self::assertNotNull($sub);
        self::assertSame(SubscriptionStatus::PENDING, $sub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
    }

    public function test_creates_with_explicit_paid_status(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID));
        self::assertSame(SubscriptionStatus::PAID, $repo->findByMemberAndSeason('m1', '2025-2026')->status());
    }

    public function test_throws_on_duplicate_season(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN));
        $this->expectException(\DomainException::class);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN));
    }
}
