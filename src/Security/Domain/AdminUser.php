<?php
namespace App\Security\Domain;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
#[ORM\Entity]
#[ORM\Table(name: 'admin_users')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;
    #[ORM\Column(type: 'string')]
    private string $password;
    #[ORM\Column(type: 'json')]
    private array $roles;
    public function __construct(Uuid $id, string $email, string $password, array $roles = ['ROLE_ADMIN'])
    {
        $this->id = $id; $this->email = $email; $this->password = $password; $this->roles = $roles;
    }
    public function id(): Uuid { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $p): void { $this->password = $p; }
    public function getRoles(): array { return array_unique([...$this->roles, 'ROLE_USER']); }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
}
