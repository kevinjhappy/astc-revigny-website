<?php

declare(strict_types=1);

namespace App\Tests\Member\Application;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MatchMemberHandlerTest extends TestCase
{
    private Member $member;
    private Uuid $memberId;

    protected function setUp(): void
    {
        $this->memberId = Uuid::generate();
        $this->member = Member::create($this->memberId, 'Dupont', 'Jean', PhoneNumber::fromString('0612345678'), null);
    }

    private function memberRepo(bool $found): MemberRepository
    {
        $member = $found ? $this->member : null;

        return new class($member) implements MemberRepository {
            public function __construct(private ?Member $member) {}
            public function save(Member $member): void {}
            public function remove(Member $member): void {}
            public function get(Uuid $id): ?Member { return null; }
            public function search(?string $query): array { return []; }
            public function findByLastNameAndPhone(string $lastName, PhoneNumber $phoneNumber): ?Member { return $this->member; }
        };
    }

    private function subRepo(?MemberSubscription $subscription): MemberSubscriptionRepository
    {
        return new class($subscription) implements MemberSubscriptionRepository {
            public function __construct(private ?MemberSubscription $subscription) {}
            public function save(MemberSubscription $subscription): void {}
            public function remove(MemberSubscription $subscription): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription { return $this->subscription; }
            public function findPaidBySeason(string $season): array { return []; }
            public function findByMember(string $memberId): array { return []; }
            public function findBySeason(string $season): array { return []; }
            public function hasAnySeason(string $season): bool { return false; }
        };
    }

    private function paidTournoisSub(): MemberSubscription
    {
        return MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID
        );
    }

    public function test_throws_when_member_not_found(): void
    {
        $handler = new MatchMemberHandler($this->memberRepo(false), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/membres du club/');
        ($handler)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_no_subscription_for_current_season(): void
    {
        $handler = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/accès aux tournois/');
        ($handler)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_is_pending(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PENDING
        );
        $handler = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/accès aux tournois/');
        ($handler)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_type_is_terrain_only(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN, SubscriptionStatus::PAID
        );
        $handler = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        ($handler)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_returns_true_when_paid_with_tournament_access(): void
    {
        $handler = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($this->paidTournoisSub()), new SeasonHelper());
        self::assertTrue(($handler)(new MatchMemberQuery('Dupont', '0612345678')));
    }

    public function test_returns_true_without_tournament_access_check_when_flag_false(): void
    {
        $handler = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        self::assertTrue(($handler)(new MatchMemberQuery('Dupont', '0612345678', false)));
    }
}
