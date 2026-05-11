<?php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'member_subscriptions')]
#[ORM\UniqueConstraint(name: 'uq_member_season', columns: ['member_id', 'season'])]
class MemberSubscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $memberId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $season;

    #[ORM\Column(type: 'string', length: 30, enumType: MembershipType::class)]
    private MembershipType $type;

    #[ORM\Column(type: 'string', length: 10, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $memberId,
        string $season,
        MembershipType $type,
        SubscriptionStatus $status,
    ) {
        $this->id = $id;
        $this->memberId = $memberId;
        $this->season = $season;
        $this->type = $type;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        Uuid $id,
        string $memberId,
        string $season,
        MembershipType $type,
        SubscriptionStatus $status = SubscriptionStatus::PENDING,
    ): self {
        return new self($id, $memberId, $season, $type, $status);
    }

    public function update(MembershipType $type, SubscriptionStatus $status): void
    {
        $this->type = $type;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): Uuid { return $this->id; }
    public function memberId(): string { return $this->memberId; }
    public function season(): string { return $this->season; }
    public function type(): MembershipType { return $this->type; }
    public function status(): SubscriptionStatus { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
