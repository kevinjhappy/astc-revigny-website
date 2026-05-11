<?php
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
class RegisterHandler {
    public function __construct(
        private TournamentRepository $tournaments,
        private RegistrationRepository $registrations,
        private MatchMemberHandler $matchMember,
    ) {}
    public function __invoke(RegisterCommand $c): RegisterResult
    {
        $t = $this->tournaments->get(Uuid::fromString($c->tournamentId))
            ?? throw new \DomainException('Tournament not found');
        if ($t->status() !== TournamentStatus::PUBLISHED)
            throw new \DomainException('Tournament is not open for registration');
        if ($t->type() === TournamentType::MEMBERS_ONLY) {
            ($this->matchMember)(new MatchMemberQuery($c->lastName, $c->phone));
        }
        $confirmedCount = $this->registrations->countConfirmed($t->id());
        $status = $confirmedCount >= $t->maxParticipants() ? RegistrationStatus::WAITING_LIST : RegistrationStatus::PENDING;
        $id = Uuid::generate();
        $reg = Registration::create($id, $t->id(), $c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone), $c->email ? Email::fromString($c->email) : null, $status);
        $this->registrations->save($reg);
        return new RegisterResult((string)$id, $status->value);
    }
}
