<?php

declare(strict_types=1);

namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(UpdateMemberSubscriptionCommand $command): void
    {
        $sub = $this->repo->get(Uuid::fromString($command->subscriptionId))
            ?? throw new \DomainException("Souscription introuvable : {$command->subscriptionId}");
        $sub->update($command->membershipType, $command->status);
        $this->repo->save($sub);
    }
}
