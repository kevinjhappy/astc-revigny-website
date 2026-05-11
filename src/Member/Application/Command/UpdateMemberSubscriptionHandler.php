<?php

declare(strict_types=1);

namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(UpdateMemberSubscriptionCommand $c): void
    {
        $sub = $this->repo->get(Uuid::fromString($c->id))
            ?? throw new \DomainException("Souscription introuvable : {$c->id}");
        $sub->update($c->type, $c->status);
        $this->repo->save($sub);
    }
}
