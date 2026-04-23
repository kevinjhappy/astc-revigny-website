<?php
namespace App\Member\Application\Command;

final class UpdateMemberCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}
