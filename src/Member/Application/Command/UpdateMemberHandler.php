<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(UpdateMemberCommand $c): void
    {
        $m = $this->repo->get(Uuid::fromString($c->id))
            ?? throw new \DomainException('Member not found');
        $m->update($c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone),
            $c->email ? Email::fromString($c->email) : null);
        $this->repo->save($m);
    }
}
