<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(CreateMemberSubscriptionCommand $c): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(),
            $c->memberId,
            $c->season,
            $c->membershipType,
            $c->status,
        );
        $this->repo->save($sub);
    }
}
