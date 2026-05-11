<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class StartNewSeasonHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(StartNewSeasonCommand $c): void
    {
        if ($this->repo->hasAnySeason($c->season)) {
            return;
        }
        $previousSeason = $this->previousSeason($c->season);
        foreach ($this->repo->findPaidBySeason($previousSeason) as $old) {
            $this->repo->save(MemberSubscription::create(
                Uuid::generate(), $old->memberId(), $c->season, $old->type(), SubscriptionStatus::PENDING,
            ));
        }
    }

    private function previousSeason(string $season): string
    {
        [$start] = explode('-', $season);
        return ($start - 1) . '-' . $start;
    }
}
