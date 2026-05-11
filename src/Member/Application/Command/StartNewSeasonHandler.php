<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class StartNewSeasonHandler
{
    public function __construct(
        private MemberSubscriptionRepository $repo,
        private SeasonHelper $seasonHelper,
    ) {}

    public function __invoke(StartNewSeasonCommand $c): void
    {
        $previousSeason = $this->seasonHelper->currentSeason();
        $paidSubscriptions = $this->repo->findPaidBySeason($previousSeason);

        foreach ($paidSubscriptions as $sub) {
            $newSub = MemberSubscription::create(
                Uuid::generate(),
                $sub->memberId(),
                $c->season,
                $sub->type(),
                SubscriptionStatus::PENDING,
            );
            $this->repo->save($newSub);
        }
    }
}
