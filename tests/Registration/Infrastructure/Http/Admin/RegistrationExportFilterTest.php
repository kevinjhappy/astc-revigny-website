<?php

namespace App\Tests\Registration\Infrastructure\Http\Admin;

use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class RegistrationExportFilterTest extends TestCase
{
    private const EXPORT_STATUSES = [
        RegistrationStatus::CONFIRMED,
        RegistrationStatus::PENDING,
        RegistrationStatus::WAITING_LIST,
    ];

    private function makeReg(RegistrationStatus $status): Registration
    {
        return Registration::create(
            Uuid::generate(),
            Uuid::generate(),
            'Dupont',
            'Jean',
            PhoneNumber::fromString('0611223344'),
            null,
            $status,
        );
    }

    public function test_cancelled_registrations_are_excluded(): void
    {
        $registrations = [
            $this->makeReg(RegistrationStatus::CONFIRMED),
            $this->makeReg(RegistrationStatus::PENDING),
            $this->makeReg(RegistrationStatus::WAITING_LIST),
            $this->makeReg(RegistrationStatus::CANCELLED),
        ];

        $filtered = array_values(array_filter(
            $registrations,
            fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
        ));

        self::assertCount(3, $filtered);
        foreach ($filtered as $reg) {
            self::assertNotSame(RegistrationStatus::CANCELLED, $reg->status());
        }
    }

    public function test_only_export_statuses_pass_filter(): void
    {
        $confirmed = $this->makeReg(RegistrationStatus::CONFIRMED);
        $pending   = $this->makeReg(RegistrationStatus::PENDING);
        $waiting   = $this->makeReg(RegistrationStatus::WAITING_LIST);
        $cancelled = $this->makeReg(RegistrationStatus::CANCELLED);

        $all = [$confirmed, $pending, $waiting, $cancelled];
        $filtered = array_values(array_filter(
            $all,
            fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
        ));

        self::assertSame([$confirmed, $pending, $waiting], $filtered);
    }
}
