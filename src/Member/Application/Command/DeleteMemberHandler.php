<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(DeleteMemberCommand $c): void
    {
        $m = $this->repo->get(Uuid::fromString($c->id));
        if ($m) $this->repo->remove($m);
    }
}
