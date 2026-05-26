<?php

declare(strict_types=1);

namespace App\Member\Application\Query;

use App\Member\Domain\MemberRepository;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;

class MatchMemberHandler
{
    public function __construct(
        private MemberRepository $repo,
        private MemberSubscriptionRepository $subscriptionRepo,
        private SeasonHelper $seasonHelper,
    ) {}

    public function __invoke(MatchMemberQuery $q): bool
    {
        $member = $this->repo->findByLastNameAndPhone($q->lastName, PhoneNumber::fromString($q->phone));
        if ($member === null) {
            throw new \DomainException('Ce tournoi est réservé aux membres du club.');
        }
        if (!$q->requireTournamentAccess) {
            return true;
        }
        $sub = $this->subscriptionRepo->findByMemberAndSeason(
            (string)$member->id(),
            $this->seasonHelper->currentSeason(),
        );
        if ($sub === null || !$sub->type()->hasTournamentAccess() || $sub->status() !== SubscriptionStatus::PAID) {
            throw new \DomainException('Ce tournoi est réservé aux membres avec accès aux tournois (cotisation Terrains + Tournois ou Terrains + Tournois + Cours).');
        }
        return true;
    }
}
