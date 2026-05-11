<?php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\StartNewSeasonCommand;
use App\Member\Application\Command\StartNewSeasonHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class StartNewSeasonHandlerTest extends TestCase
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
            public function findPaidBySeason(string $s): array {
                return array_values(array_filter($this->store,
                    fn($sub) => $sub->season() === $s && $sub->status() === SubscriptionStatus::PAID));
            }
            public function findByMember(string $m): array {
                return array_values(array_filter($this->store, fn($s) => $s->memberId() === $m));
            }
            public function findBySeason(string $s): array {
                return array_values(array_filter($this->store, fn($sub) => $sub->season() === $s));
            }
            public function hasAnySeason(string $s): bool {
                foreach ($this->store as $sub) { if ($sub->season() === $s) return true; }
                return false;
            }
        };
    }

    public function test_creates_pending_for_paid_members_of_previous_season(): void
    {
        $repo = $this->makeRepo();
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID));
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm2', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PENDING));

        (new StartNewSeasonHandler($repo))(new StartNewSeasonCommand('2026-2027'));

        $newSub = $repo->findByMemberAndSeason('m1', '2026-2027');
        self::assertNotNull($newSub);
        self::assertSame(SubscriptionStatus::PENDING, $newSub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $newSub->type());
        self::assertNull($repo->findByMemberAndSeason('m2', '2026-2027'));
    }

    public function test_idempotent(): void
    {
        $repo = $this->makeRepo();
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID));
        $handler = new StartNewSeasonHandler($repo);
        ($handler)(new StartNewSeasonCommand('2026-2027'));
        ($handler)(new StartNewSeasonCommand('2026-2027'));
        self::assertCount(2, $repo->store);
    }
}
