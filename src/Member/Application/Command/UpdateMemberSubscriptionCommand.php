<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;

final class UpdateMemberSubscriptionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly MembershipType $type,
        public readonly SubscriptionStatus $status,
    ) {}
}
