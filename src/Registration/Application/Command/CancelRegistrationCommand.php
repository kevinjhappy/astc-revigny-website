<?php
namespace App\Registration\Application\Command;
final class CancelRegistrationCommand { public function __construct(public readonly string $id) {} }
