<?php

declare(strict_types=1);

namespace App\Member\Infrastructure\Doctrine;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMemberSubscriptionRepository implements MemberSubscriptionRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(MemberSubscription $s): void
    {
        $this->em->persist($s);
        $this->em->flush();
    }

    public function remove(MemberSubscription $s): void
    {
        $this->em->remove($s);
        $this->em->flush();
    }

    public function get(Uuid $id): ?MemberSubscription
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findOneBy(['id' => (string)$id]);
    }

    public function findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findOneBy(['memberId' => $memberId, 'season' => $season]);
    }

    public function findPaidBySeason(string $season): array
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findBy(['season' => $season, 'status' => SubscriptionStatus::PAID]);
    }

    public function findByMember(string $memberId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')->from(MemberSubscription::class, 's')
            ->where('s.memberId = :memberId')
            ->setParameter('memberId', $memberId)
            ->orderBy('s.season', 'DESC')
            ->getQuery()->getResult();
    }

    public function findBySeason(string $season): array
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findBy(['season' => $season]);
    }

    public function hasAnySeason(string $season): bool
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->count(['season' => $season]) > 0;
    }
}
