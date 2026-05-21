<?php

namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(DeleteMemberCommand $command): void
    {
        $member = $this->repo->get(Uuid::fromString($command->id));
        if ($member) {
            $this->repo->remove($member);
        }
    }
}
