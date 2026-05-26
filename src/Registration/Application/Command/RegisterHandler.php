<?php

declare(strict_types=1);

namespace App\Registration\Application\Command;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use App\Tournament\Domain\TournamentType;

class RegisterHandler
{
    public function __construct(
        private TournamentRepository $tournaments,
        private RegistrationRepository $registrations,
        private MatchMemberHandler $matchMember,
    ) {}

    public function __invoke(RegisterCommand $command): RegisterResult
    {
        $tournament = $this->tournaments->get(Uuid::fromString($command->tournamentId))
            ?? throw new \DomainException('Tournoi introuvable.');
        if ($tournament->status() !== TournamentStatus::PUBLISHED) {
            throw new \DomainException('Ce tournoi n\'est pas ouvert aux inscriptions.');
        }
        if ($tournament->type() === TournamentType::MEMBERS_ONLY) {
            ($this->matchMember)(new MatchMemberQuery($command->lastName, $command->phone));
        }
        $confirmedCount = $this->registrations->countConfirmed($tournament->id());
        $status = $confirmedCount >= $tournament->maxParticipants() ? RegistrationStatus::WAITING_LIST : RegistrationStatus::PENDING;
        $id = Uuid::generate();
        $registration = Registration::create($id, $tournament->id(), $command->lastName, $command->firstName,
            PhoneNumber::fromString($command->phone), $command->email ? Email::fromString($command->email) : null, $status);
        $this->registrations->save($registration);

        return new RegisterResult((string)$id, $status->value);
    }
}
