<?php
namespace App\Member\Application\Query;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;

final class MatchMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(MatchMemberQuery $q): bool
    {
        return $this->repo->findByLastNameAndPhone($q->lastName, PhoneNumber::fromString($q->phone)) !== null;
    }
}
