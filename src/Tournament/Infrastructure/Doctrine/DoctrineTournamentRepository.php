<?php

namespace App\Tournament\Infrastructure\Doctrine;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTournamentRepository implements TournamentRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Tournament $tournament): void
    {
        $this->em->persist($tournament);
        $this->em->flush();
    }

    public function get(Uuid $id): ?Tournament
    {
        return $this->em->getRepository(Tournament::class)->findOneBy(['id' => (string)$id]);
    }

    public function all(): array
    {
        return $this->em->createQueryBuilder()->select('t')->from(Tournament::class, 't')
            ->orderBy('t.startDate', 'DESC')->getQuery()->getResult();
    }

    public function published(): array
    {
        return $this->em->getRepository(Tournament::class)
            ->findBy(['status' => TournamentStatus::PUBLISHED->value], ['startDate' => 'ASC']);
    }

    public function publishedOrClosed(): array
    {
        return $this->em->createQueryBuilder()->select('t')->from(Tournament::class, 't')
            ->where('t.status IN (:statuses)')
            ->setParameter('statuses', [TournamentStatus::PUBLISHED->value, TournamentStatus::CLOSED->value])
            ->orderBy('t.startDate', 'ASC')
            ->getQuery()->getResult();
    }

    public function notClosed(): array
    {
        return $this->em->createQueryBuilder()->select('t')->from(Tournament::class, 't')
            ->where('t.status != :closed')
            ->setParameter('closed', TournamentStatus::CLOSED->value)
            ->orderBy('t.startDate', 'DESC')
            ->getQuery()->getResult();
    }
}
