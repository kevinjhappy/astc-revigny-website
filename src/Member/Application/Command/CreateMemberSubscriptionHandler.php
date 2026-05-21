<?php

declare(strict_types=1);

namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(CreateMemberSubscriptionCommand $command): void
    {
        if ($this->repo->findByMemberAndSeason($command->memberId, $command->season) !== null) {
            throw new \DomainException("Une souscription existe déjà pour ce membre pour la saison {$command->season}.");
        }
        $this->repo->save(MemberSubscription::create(
            Uuid::generate(),
            $command->memberId,
            $command->season,
            $command->membershipType,
            $command->status,
        ));
    }
}
