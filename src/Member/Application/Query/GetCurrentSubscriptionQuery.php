<?php

declare(strict_types=1);

namespace App\Member\Application\Query;

final class GetCurrentSubscriptionQuery
{
    public function __construct(public readonly string $memberId) {}
}
