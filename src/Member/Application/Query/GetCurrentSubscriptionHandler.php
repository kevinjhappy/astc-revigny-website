<?php

declare(strict_types=1);

namespace App\Member\Application\Query;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;

final class GetCurrentSubscriptionHandler
{
    public function __construct(
        private MemberSubscriptionRepository $repo,
        private SeasonHelper $seasonHelper,
    ) {}

    public function __invoke(GetCurrentSubscriptionQuery $q, ?\DateTimeImmutable $now = null): ?MemberSubscription
    {
        return $this->repo->findByMemberAndSeason(
            $q->memberId,
            $this->seasonHelper->currentSeason($now),
        );
    }
}
