<?php

namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(UpdateMemberCommand $command): void
    {
        $member = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('Member not found');
        $birthDate = $command->birthDate ? \DateTimeImmutable::createFromFormat('d/m/Y', $command->birthDate) ?: null : null;
        $member->update($command->lastName, $command->firstName,
            PhoneNumber::fromString($command->phone),
            $command->email ? Email::fromString($command->email) : null,
            $birthDate,
            $command->postalAddress ?: null);
        $this->repo->save($member);
    }
}
