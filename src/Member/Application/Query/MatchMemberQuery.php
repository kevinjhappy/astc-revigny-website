<?php

declare(strict_types=1);

namespace App\Member\Application\Query;

final class MatchMemberQuery
{
    public function __construct(
        public readonly string $lastName,
        public readonly string $phone,
        public readonly bool $requireTournamentAccess = true,
    ) {}
}
