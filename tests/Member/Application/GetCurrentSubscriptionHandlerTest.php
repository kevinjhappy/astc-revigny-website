<?php

declare(strict_types=1);

namespace App\Tests\Member\Application;

use App\Member\Application\Query\GetCurrentSubscriptionHandler;
use App\Member\Application\Query\GetCurrentSubscriptionQuery;
use App\Member\Application\Query\GetSubscriptionHistoryHandler;
use App\Member\Application\Query\GetSubscriptionHistoryQuery;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SeasonHelper;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class GetCurrentSubscriptionHandlerTest extends TestCase
{
    private function makeRepo(array $subs): MemberSubscriptionRepository
    {
        return new class($subs) implements MemberSubscriptionRepository {
            public function __construct(private array $store) {}
            public function save(MemberSubscription $s): void {}
            public function remove(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription {
                foreach ($this->store as $sub) {
                    if ($sub->memberId() === $m && $sub->season() === $s) return $sub;
                }
                return null;
            }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array {
                return array_values(array_filter($this->store, fn($s) => $s->memberId() === $m));
            }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
    }

    public function test_returns_current_season_subscription(): void
    {
        // May (month 5) < 9, so currentSeason() yields '2025-2026'
        $now = new \DateTimeImmutable('2026-05-01');
        $sub = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS);
        $repo = $this->makeRepo([$sub]);
        $handler = new GetCurrentSubscriptionHandler($repo, new SeasonHelper(), $now);
        $result = ($handler)(new GetCurrentSubscriptionQuery('m1'));
        self::assertSame($sub, $result);
    }

    public function test_returns_null_when_no_subscription(): void
    {
        // May (month 5) < 9, so currentSeason() yields '2025-2026'
        $now = new \DateTimeImmutable('2026-05-01');
        $repo = $this->makeRepo([]);
        $handler = new GetCurrentSubscriptionHandler($repo, new SeasonHelper(), $now);
        self::assertNull(($handler)(new GetCurrentSubscriptionQuery('m1')));
    }

    public function test_history_returns_all_subscriptions_for_member(): void
    {
        $sub1 = MemberSubscription::create(Uuid::generate(), 'm1', '2024-2025', MembershipType::TERRAIN);
        $sub2 = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS);
        $repo = $this->makeRepo([$sub1, $sub2]);
        $handler = new GetSubscriptionHistoryHandler($repo);
        $result = ($handler)(new GetSubscriptionHistoryQuery('m1'));
        self::assertCount(2, $result);
    }
}
