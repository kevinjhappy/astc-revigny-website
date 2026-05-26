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
            public function __construct(private ?Member $m) {}
            public function save(Member $m): void {}
            public function remove(Member $m): void {}
            public function get(Uuid $id): ?Member { return null; }
            public function search(?string $q): array { return []; }
            public function findByLastNameAndPhone(string $l, PhoneNumber $p): ?Member { return $this->m; }
        };
    }

    private function subRepo(?MemberSubscription $sub): MemberSubscriptionRepository
    {
        return new class($sub) implements MemberSubscriptionRepository {
            public function __construct(private ?MemberSubscription $sub) {}
            public function save(MemberSubscription $s): void {}
            public function remove(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return $this->sub; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
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
        $h = new MatchMemberHandler($this->memberRepo(false), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/membres du club/');
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_no_subscription_for_current_season(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/accès aux tournois/');
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_is_pending(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PENDING
        );
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/accès aux tournois/');
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_type_is_terrain_only(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN, SubscriptionStatus::PAID
        );
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_returns_true_when_paid_with_tournament_access(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($this->paidTournoisSub()), new SeasonHelper());
        self::assertTrue(($h)(new MatchMemberQuery('Dupont', '0612345678')));
    }

    public function test_returns_true_without_tournament_access_check_when_flag_false(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        self::assertTrue(($h)(new MatchMemberQuery('Dupont', '0612345678', false)));
    }
}
