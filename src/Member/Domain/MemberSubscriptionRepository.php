<?php

declare(strict_types=1);

namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Uuid;

interface MemberSubscriptionRepository
{
    public function save(MemberSubscription $subscription): void;
    public function remove(MemberSubscription $subscription): void;
    public function get(Uuid $id): ?MemberSubscription;
    public function findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription;
    /** @return MemberSubscription[] */
    public function findPaidBySeason(string $season): array;
    /** @return MemberSubscription[] triées par saison DESC */
    public function findByMember(string $memberId): array;
    /** @return MemberSubscription[] */
    public function findBySeason(string $season): array;
    public function hasAnySeason(string $season): bool;
}
