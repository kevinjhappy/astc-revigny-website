<?php
namespace App\Tests\Registration\Domain;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;
final class RegistrationTest extends TestCase
{
    private function make(RegistrationStatus $s = RegistrationStatus::PENDING): Registration
    {
        return Registration::create(Uuid::generate(), Uuid::generate(), 'A', 'B', PhoneNumber::fromString('0612345678'), null, $s);
    }
    public function test_confirm_from_pending(): void { $r = $this->make(); $r->confirm(); self::assertSame(RegistrationStatus::CONFIRMED, $r->status()); }
    public function test_cannot_confirm_cancelled(): void { $r = $this->make(); $r->cancel(); $this->expectException(\DomainException::class); $r->confirm(); }
    public function test_promote_from_waiting_list(): void { $r = $this->make(RegistrationStatus::WAITING_LIST); $r->promoteToPending(); self::assertSame(RegistrationStatus::PENDING, $r->status()); }
    public function test_cannot_promote_non_waiting(): void { $this->expectException(\DomainException::class); $this->make()->promoteToPending(); }
}
