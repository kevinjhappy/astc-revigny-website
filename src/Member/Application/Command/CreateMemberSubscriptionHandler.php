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
        if ($this->repo->findByMemberAndSeason($c->memberId, $c->season) !== null) {
            throw new \DomainException("Une souscription existe déjà pour ce membre pour la saison {$c->season}.");
        }
        $this->repo->save(MemberSubscription::create(
            Uuid::generate(), $c->memberId, $c->season, $c->type, $c->status,
        ));
    }
}
