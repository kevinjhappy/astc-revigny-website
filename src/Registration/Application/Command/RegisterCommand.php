<?php
namespace App\Registration\Application\Command;
final class RegisterCommand {
    public function __construct(
        public readonly string $tournamentId,
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}
