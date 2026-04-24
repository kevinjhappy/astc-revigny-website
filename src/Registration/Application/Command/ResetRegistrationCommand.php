<?php
namespace App\Registration\Application\Command;
final class ResetRegistrationCommand {
    public function __construct(public readonly string $id) {}
}
