<?php

declare(strict_types=1);

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
        $tournament = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, $max, null);
        $tournament->publish();

        return $tournament;
    }

    private function fakes(Tournament $tournament, array $registrations = []): array
    {
        $tournamentRepo = new class($tournament) implements TournamentRepository {
            public function __construct(private Tournament $tournament) {}
            public function save(Tournament $tournament): void {}
            public function get(Uuid $id): ?Tournament { return $this->tournament; }
            public function all(): array { return [$this->tournament]; }
            public function published(): array { return [$this->tournament]; }
            public function publishedOrClosed(): array { return [$this->tournament]; }
            public function notClosed(): array { return [$this->tournament]; }
        };
        $registrationRepo = new class($registrations) implements RegistrationRepository {
            public array $store;
            public function __construct(array $init) { $this->store = $init; }
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
        $matchHandler = new class extends MatchMemberHandler {
            public function __construct() {}
            public bool $ok = true;
            public function __invoke(MatchMemberQuery $query): bool {
                if (!$this->ok) {
                    throw new \DomainException('Ce tournoi est réservé aux membres du club.');
                }

                return true;
            }
        };

        return [$tournamentRepo, $registrationRepo, $matchHandler];
    }

    public function test_creates_pending_if_space(): void
    {
        $tournament = $this->openTournament(2);
        [$tournamentRepo, $registrationRepo, $matchHandler] = $this->fakes($tournament);
        $result = (new RegisterHandler($tournamentRepo, $registrationRepo, $matchHandler))(new RegisterCommand((string)$tournament->id(), 'A', 'B', '0612345678', null));
        self::assertSame('PENDING', $result->status);
        self::assertCount(1, $registrationRepo->store);
    }

    public function test_goes_to_waiting_list_when_full(): void
    {
        $tournament = $this->openTournament(1);
        $confirmed = Registration::create(Uuid::generate(), $tournament->id(), 'X', 'Y',
            PhoneNumber::fromString('0611111111'), null, RegistrationStatus::CONFIRMED);
        [$tournamentRepo, $registrationRepo, $matchHandler] = $this->fakes($tournament, [(string)$confirmed->id() => $confirmed]);
        $result = (new RegisterHandler($tournamentRepo, $registrationRepo, $matchHandler))(new RegisterCommand((string)$tournament->id(), 'A', 'B', '0612345678', null));
        self::assertSame('WAITING_LIST', $result->status);
    }

    public function test_rejects_members_only_when_not_member(): void
    {
        $tournament = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::MEMBERS_ONLY, 10, null);
        $tournament->publish();
        [$tournamentRepo, $registrationRepo, $matchHandler] = $this->fakes($tournament);
        $matchHandler->ok = false;
        $this->expectException(\DomainException::class);
        (new RegisterHandler($tournamentRepo, $registrationRepo, $matchHandler))(new RegisterCommand((string)$tournament->id(), 'A', 'B', '0612345678', null));
    }

    public function test_rejects_if_tournament_not_published(): void
    {
        $tournament = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, 10, null);
        [$tournamentRepo, $registrationRepo, $matchHandler] = $this->fakes($tournament);
        $this->expectException(\DomainException::class);
        (new RegisterHandler($tournamentRepo, $registrationRepo, $matchHandler))(new RegisterCommand((string)$tournament->id(), 'A', 'B', '0612345678', null));
    }
}
