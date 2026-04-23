<?php
namespace App\Tests\Registration\Application;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Registration\Application\Command\RegisterCommand;
use App\Registration\Application\Command\RegisterHandler;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;
use PHPUnit\Framework\TestCase;

final class RegisterHandlerTest extends TestCase
{
    private function openTournament(int $max = 2): Tournament
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, $max, null);
        $t->publish();
        return $t;
    }

    private function fakes(Tournament $t, array $regs = []): array
    {
        $tRepo = new class($t) implements TournamentRepository {
            public function __construct(private Tournament $t) {}
            public function save(Tournament $x): void {}
            public function get(Uuid $id): ?Tournament { return $this->t; }
            public function all(): array { return [$this->t]; }
            public function published(): array { return [$this->t]; }
        };
        $rRepo = new class($regs) implements RegistrationRepository {
            public array $store;
            public function __construct(array $init) { $this->store = $init; }
            public function save(Registration $r): void { $this->store[(string)$r->id()] = $r; }
            public function remove(Registration $r): void { unset($this->store[(string)$r->id()]); }
            public function get(Uuid $id): ?Registration { return $this->store[(string)$id] ?? null; }
            public function byTournament(Uuid $id): array { return array_values($this->store); }
            public function countConfirmed(Uuid $id): int {
                return count(array_filter($this->store, fn($r)=>$r->status()===RegistrationStatus::CONFIRMED));
            }
            public function firstWaitingList(Uuid $id): ?Registration {
                foreach ($this->store as $r) if ($r->status() === RegistrationStatus::WAITING_LIST) return $r;
                return null;
            }
            public function all(?string $t, ?string $s): array { return array_values($this->store); }
        };
        $match = new class extends MatchMemberHandler {
            public function __construct() {}
            public bool $ok = true;
            public function __invoke(MatchMemberQuery $q): bool { return $this->ok; }
        };
        return [$tRepo, $rRepo, $match];
    }

    public function test_creates_pending_if_space(): void
    {
        $t = $this->openTournament(2);
        [$tr, $rr, $m] = $this->fakes($t);
        $result = (new RegisterHandler($tr, $rr, $m))(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
        self::assertSame('PENDING', $result->status);
        self::assertCount(1, $rr->store);
    }

    public function test_goes_to_waiting_list_when_full(): void
    {
        $t = $this->openTournament(1);
        $confirmed = Registration::create(Uuid::generate(), $t->id(), 'X','Y',
            PhoneNumber::fromString('0611111111'), null, RegistrationStatus::CONFIRMED);
        [$tr, $rr, $m] = $this->fakes($t, [(string)$confirmed->id() => $confirmed]);
        $result = (new RegisterHandler($tr, $rr, $m))(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
        self::assertSame('WAITING_LIST', $result->status);
    }

    public function test_rejects_members_only_when_not_member(): void
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::MEMBERS_ONLY, 10, null);
        $t->publish();
        [$tr, $rr, $m] = $this->fakes($t);
        $m->ok = false;
        $this->expectException(\DomainException::class);
        (new RegisterHandler($tr, $rr, $m))(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
    }

    public function test_rejects_if_tournament_not_published(): void
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, 10, null);
        [$tr, $rr, $m] = $this->fakes($t);
        $this->expectException(\DomainException::class);
        (new RegisterHandler($tr, $rr, $m))(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
    }
}
