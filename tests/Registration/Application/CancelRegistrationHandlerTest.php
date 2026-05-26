<?php

namespace App\Tests\Registration\Application;

use App\Registration\Application\Command\CancelRegistrationCommand;
use App\Registration\Application\Command\CancelRegistrationHandler;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CancelRegistrationHandlerTest extends TestCase
{
    public function test_cancelling_confirmed_promotes_first_waiting_list(): void
    {
        $tournamentId = Uuid::generate();
        $confirmed = Registration::create(Uuid::generate(), $tournamentId, 'C', 'f', PhoneNumber::fromString('0611111111'), null, RegistrationStatus::CONFIRMED);
        $waiting = Registration::create(Uuid::generate(), $tournamentId, 'W', 'w', PhoneNumber::fromString('0622222222'), null, RegistrationStatus::WAITING_LIST);
        $repo = new class([$confirmed, $waiting]) implements RegistrationRepository {
            public array $store = [];
            public function __construct(array $init)
            {
                foreach ($init as $registration) {
                    $this->store[(string)$registration->id()] = $registration;
                }
            }
            public function save(Registration $registration): void { $this->store[(string)$registration->id()] = $registration; }
            public function remove(Registration $registration): void { unset($this->store[(string)$registration->id()]); }
            public function get(Uuid $id): ?Registration { return $this->store[(string)$id] ?? null; }
            public function byTournament(Uuid $id): array { return array_values($this->store); }
            public function countConfirmed(Uuid $id): int {
                return count(array_filter($this->store, fn($registration) => $registration->status() === RegistrationStatus::CONFIRMED));
            }
            public function firstWaitingList(Uuid $id): ?Registration {
                foreach ($this->store as $registration) {
                    if ($registration->status() === RegistrationStatus::WAITING_LIST) {
                        return $registration;
                    }
                }

                return null;
            }
            public function all(?string $tournamentId, ?string $status, array $allowedTournamentIds = []): array { return array_values($this->store); }
        };
        (new CancelRegistrationHandler($repo))(new CancelRegistrationCommand((string)$confirmed->id()));
        self::assertSame(RegistrationStatus::CANCELLED, $repo->get($confirmed->id())->status());
        self::assertSame(RegistrationStatus::PENDING, $repo->get($waiting->id())->status());
    }
}
