<?php
namespace App\Registration\Application\Command;
final class RegisterResult {
    public function __construct(public readonly string $id, public readonly string $status) {}
}
