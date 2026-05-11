<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(UpdateMemberSubscriptionCommand $c): void
    {
        $sub = $this->repo->get(Uuid::fromString($c->subscriptionId))
            ?? throw new \DomainException("Souscription introuvable : {$c->subscriptionId}");
        $sub->update($c->membershipType, $c->status);
        $this->repo->save($sub);
    }
}
