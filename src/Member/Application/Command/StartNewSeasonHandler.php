<?php

declare(strict_types=1);

namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class StartNewSeasonHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(StartNewSeasonCommand $command): void
    {
        $previousSeason = $this->previousSeason($command->season);
        foreach ($this->repo->findPaidBySeason($previousSeason) as $subscription) {
            if ($this->repo->findByMemberAndSeason($subscription->memberId(), $command->season) !== null) {
                continue;
            }
            $this->repo->save(MemberSubscription::create(
                Uuid::generate(),
                $subscription->memberId(),
                $command->season,
                $subscription->type(),
                SubscriptionStatus::PENDING,
            ));
        }
    }

    private function previousSeason(string $season): string
    {
        [$start] = explode('-', $season);
        return ($start - 1) . '-' . $start;
    }
}
