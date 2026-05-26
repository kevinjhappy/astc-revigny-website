<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;

final class UpdateMemberSubscriptionCommand
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly MembershipType $membershipType,
        public readonly SubscriptionStatus $status,
    ) {}
}
