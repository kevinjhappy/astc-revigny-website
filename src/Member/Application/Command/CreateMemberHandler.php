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

    public function __invoke(CreateMemberCommand $command): Uuid
    {
        $id = Uuid::generate();
        $birthDate = $command->birthDate ? \DateTimeImmutable::createFromFormat('d/m/Y', $command->birthDate) ?: null : null;
        $member = Member::create($id, $command->lastName, $command->firstName,
            PhoneNumber::fromString($command->phone),
            $command->email ? Email::fromString($command->email) : null,
            $birthDate,
            $command->postalAddress ?: null);
        $this->repo->save($member);

        return $id;
    }
}
