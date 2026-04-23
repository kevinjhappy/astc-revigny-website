<?php
namespace App\Member\Application\Command;

final class CreateMemberCommand
{
    public function __construct(
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}
