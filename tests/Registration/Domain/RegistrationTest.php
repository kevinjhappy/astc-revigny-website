<?php

namespace App\Tests\Registration\Domain;

use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class RegistrationTest extends TestCase
{
    private function make(RegistrationStatus $status = RegistrationStatus::PENDING): Registration
    {
        return Registration::create(Uuid::generate(), Uuid::generate(), 'A', 'B', PhoneNumber::fromString('0612345678'), null, $status);
    }

    public function test_confirm_from_pending(): void
    {
        $registration = $this->make();
        $registration->confirm();
        self::assertSame(RegistrationStatus::CONFIRMED, $registration->status());
    }

    public function test_cannot_confirm_cancelled(): void
    {
        $registration = $this->make();
        $registration->cancel();
        $this->expectException(\DomainException::class);
        $registration->confirm();
    }

    public function test_promote_from_waiting_list(): void
    {
        $registration = $this->make(RegistrationStatus::WAITING_LIST);
        $registration->promoteToPending();
        self::assertSame(RegistrationStatus::PENDING, $registration->status());
    }

    public function test_cannot_promote_non_waiting(): void
    {
        $this->expectException(\DomainException::class);
        $this->make()->promoteToPending();
    }
}
