<?php
namespace App\Tournament\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tournaments')]
class Tournament
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'string', length: 150)]
    private string $name;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startDate;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endDate;
    #[ORM\Column(type: 'string', enumType: TournamentType::class)]
    private TournamentType $type;
    #[ORM\Column(type: 'integer')]
    private int $maxParticipants;
    #[ORM\Column(type: 'string', enumType: TournamentStatus::class)]
    private TournamentStatus $status;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    private function __construct(Uuid $id, string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc)
    {
        if ($e < $s) throw new \DomainException('endDate before startDate');
        if ($max <= 0) throw new \DomainException('maxParticipants must be > 0');
        $this->id = $id; $this->name = $name;
        $this->startDate = $s; $this->endDate = $e;
        $this->type = $type; $this->maxParticipants = $max;
        $this->status = TournamentStatus::DRAFT; $this->description = $desc;
    }

    public static function create(Uuid $id, string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc): self
    {
        return new self($id, $name, $s, $e, $type, $max, $desc);
    }

    public function update(string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc): void
    {
        if ($e < $s) throw new \DomainException('endDate before startDate');
        if ($max <= 0) throw new \DomainException('maxParticipants must be > 0');
        $this->name = $name; $this->startDate = $s; $this->endDate = $e;
        $this->type = $type; $this->maxParticipants = $max; $this->description = $desc;
    }

    public function publish(): void
    {
        if ($this->status !== TournamentStatus::DRAFT) throw new \DomainException('only DRAFT can be published');
        $this->status = TournamentStatus::PUBLISHED;
    }

    public function close(): void
    {
        if ($this->status !== TournamentStatus::PUBLISHED) throw new \DomainException('only PUBLISHED can be closed');
        $this->status = TournamentStatus::CLOSED;
    }

    public function id(): Uuid { return $this->id; }
    public function name(): string { return $this->name; }
    public function startDate(): \DateTimeImmutable { return $this->startDate; }
    public function endDate(): \DateTimeImmutable { return $this->endDate; }
    public function type(): TournamentType { return $this->type; }
    public function maxParticipants(): int { return $this->maxParticipants; }
    public function status(): TournamentStatus { return $this->status; }
    public function description(): ?string { return $this->description; }
}
