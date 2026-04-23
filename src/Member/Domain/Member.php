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

    private function __construct(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email)
    {
        $this->id = $id;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
    }

    public static function create(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email): self
    {
        return new self($id, $lastName, $firstName, $phone, $email);
    }

    public function update(string $lastName, string $firstName, PhoneNumber $phone, ?Email $email): void
    {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
    }

    public function id(): Uuid { return $this->id; }
    public function lastName(): string { return $this->lastName; }
    public function firstName(): string { return $this->firstName; }
    public function phone(): PhoneNumber { return $this->phone; }
    public function email(): ?Email { return $this->email; }
}
