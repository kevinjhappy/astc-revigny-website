<?php
namespace App\Tests\Member\Domain;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MemberSubscriptionTest extends TestCase
{
    public function test_creation_defaults_to_pending(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'member-uuid-1', '2025-2026', MembershipType::TERRAIN_TOURNOIS
        );
        self::assertSame(SubscriptionStatus::PENDING, $sub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame('2025-2026', $sub->season());
        self::assertSame('member-uuid-1', $sub->memberId());
        self::assertNotNull($sub->createdAt());
        self::assertNotNull($sub->updatedAt());
    }

    public function test_creation_with_explicit_status(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'm2', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID
        );
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }

    public function test_update_changes_type_and_status(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN
        );
        $sub->update(MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID);
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }
}
