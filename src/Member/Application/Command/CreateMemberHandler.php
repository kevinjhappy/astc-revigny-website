<?php
namespace App\Member\Application\Command;

use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(CreateMemberCommand $c): Uuid
    {
        $id = Uuid::generate();
        $m = Member::create($id, $c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone),
            $c->email ? Email::fromString($c->email) : null);
        $this->repo->save($m);
        return $id;
    }
}
