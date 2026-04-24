<?php
namespace App\Registration\Domain;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'registrations')]
class Registration
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'uuid')]
    private Uuid $tournamentId;
    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;
    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;
    #[ORM\Column(type: 'phone_number')]
    private PhoneNumber $phone;
    #[ORM\Column(type: 'email', nullable: true)]
    private ?Email $email;
    #[ORM\Column(type: 'string', enumType: RegistrationStatus::class)]
    private RegistrationStatus $status;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $registeredAt;

    private function __construct(Uuid $id, Uuid $tournamentId, string $lastName, string $firstName,
        PhoneNumber $phone, ?Email $email, RegistrationStatus $status)
    {
        $this->id = $id; $this->tournamentId = $tournamentId;
        $this->lastName = $lastName; $this->firstName = $firstName;
        $this->phone = $phone; $this->email = $email;
        $this->status = $status; $this->registeredAt = new \DateTimeImmutable();
    }

    public static function create(Uuid $id, Uuid $tournamentId, string $lastName, string $firstName,
        PhoneNumber $phone, ?Email $email, RegistrationStatus $status): self
    {
        return new self($id, $tournamentId, $lastName, $firstName, $phone, $email, $status);
    }

    public function confirm(): void
    {
        if ($this->status === RegistrationStatus::CANCELLED) throw new \DomainException('cannot confirm cancelled');
        if ($this->status === RegistrationStatus::WAITING_LIST) throw new \DomainException('promote first');
        $this->status = RegistrationStatus::CONFIRMED;
    }

    public function cancel(): void { $this->status = RegistrationStatus::CANCELLED; }

    public function resetToPending(): void { $this->status = RegistrationStatus::PENDING; }

    public function promoteToPending(): void
    {
        if ($this->status !== RegistrationStatus::WAITING_LIST) throw new \DomainException('only WAITING_LIST can be promoted');
        $this->status = RegistrationStatus::PENDING;
    }

    public function id(): Uuid { return $this->id; }
    public function tournamentId(): Uuid { return $this->tournamentId; }
    public function lastName(): string { return $this->lastName; }
    public function firstName(): string { return $this->firstName; }
    public function phone(): PhoneNumber { return $this->phone; }
    public function email(): ?Email { return $this->email; }
    public function status(): RegistrationStatus { return $this->status; }
    public function registeredAt(): \DateTimeImmutable { return $this->registeredAt; }
}
