<?php

declare(strict_types=1);

namespace App\Member\Application\Query;

use App\Member\Domain\MemberSubscriptionRepository;

final class GetSubscriptionHistoryHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    /** @return \App\Member\Domain\MemberSubscription[] */
    public function __invoke(GetSubscriptionHistoryQuery $q): array
    {
        return $this->repo->findByMember($q->memberId);
    }
}
