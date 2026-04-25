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
        $tId = Uuid::generate();
        $confirmed = Registration::create(Uuid::generate(), $tId, 'C','f', PhoneNumber::fromString('0611111111'), null, RegistrationStatus::CONFIRMED);
        $waiting = Registration::create(Uuid::generate(), $tId, 'W','w', PhoneNumber::fromString('0622222222'), null, RegistrationStatus::WAITING_LIST);
        $repo = new class([$confirmed, $waiting]) implements RegistrationRepository {
            public array $store = [];
            public function __construct(array $init) { foreach ($init as $r) $this->store[(string)$r->id()] = $r; }
            public function save(Registration $r): void { $this->store[(string)$r->id()] = $r; }
            public function remove(Registration $r): void { unset($this->store[(string)$r->id()]); }
            public function get(Uuid $id): ?Registration { return $this->store[(string)$id] ?? null; }
            public function byTournament(Uuid $id): array { return array_values($this->store); }
            public function countConfirmed(Uuid $id): int { return count(array_filter($this->store, fn($r)=>$r->status()===RegistrationStatus::CONFIRMED)); }
            public function firstWaitingList(Uuid $id): ?Registration { foreach ($this->store as $r) if ($r->status() === RegistrationStatus::WAITING_LIST) return $r; return null; }
            public function all(?string $t, ?string $s, array $allowedTournamentIds = []): array { return array_values($this->store); }
        };
        (new CancelRegistrationHandler($repo))(new CancelRegistrationCommand((string)$confirmed->id()));
        self::assertSame(RegistrationStatus::CANCELLED, $repo->get($confirmed->id())->status());
        self::assertSame(RegistrationStatus::PENDING, $repo->get($waiting->id())->status());
    }
}
