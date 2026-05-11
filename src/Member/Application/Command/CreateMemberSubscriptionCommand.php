<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;

final class CreateMemberSubscriptionCommand
{
    public function __construct(
        public readonly string $memberId,
        public readonly string $season,
        public readonly MembershipType $type,
        public readonly SubscriptionStatus $status = SubscriptionStatus::PENDING,
    ) {}
}
