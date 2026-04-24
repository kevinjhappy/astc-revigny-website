<?php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'members')]
class Member
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'phone_number')]
    private PhoneNumber $phone;

    #[ORM\Column(type: 'email', nullable: true)]
    private ?Email $email;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $birthDate;

    private function __construct(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email, ?\DateTimeImmutable $birthDate)
    {
        $this->id = $id;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
        $this->birthDate = $birthDate;
    }

    public static function create(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email, ?\DateTimeImmutable $birthDate = null): self
    {
        return new self($id, $lastName, $firstName, $phone, $email, $birthDate);
    }

    public function update(string $lastName, string $firstName, PhoneNumber $phone, ?Email $email, ?\DateTimeImmutable $birthDate): void
    {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
        $this->birthDate = $birthDate;
    }

    public function id(): Uuid { return $this->id; }
    public function lastName(): string { return $this->lastName; }
    public function firstName(): string { return $this->firstName; }
    public function phone(): PhoneNumber { return $this->phone; }
    public function email(): ?Email { return $this->email; }
    public function birthDate(): ?\DateTimeImmutable { return $this->birthDate; }
}
