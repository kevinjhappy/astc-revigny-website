<?php
namespace App\Registration\Infrastructure\Doctrine;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;
final class DoctrineRegistrationRepository implements RegistrationRepository
{
    public function __construct(private EntityManagerInterface $em) {}
    public function save(Registration $r): void { $this->em->persist($r); $this->em->flush(); }
    public function remove(Registration $r): void { $this->em->remove($r); $this->em->flush(); }
    public function get(Uuid $id): ?Registration
    {
        return $this->em->getRepository(Registration::class)->findOneBy(['id' => (string)$id]);
    }
    public function byTournament(Uuid $tournamentId): array
    {
        return $this->em->getRepository(Registration::class)
            ->findBy(['tournamentId' => (string)$tournamentId], ['registeredAt' => 'ASC']);
    }
    public function countConfirmed(Uuid $tournamentId): int
    {
        return (int)$this->em->createQueryBuilder()->select('COUNT(r.id)')
            ->from(Registration::class, 'r')
            ->where('r.tournamentId = :tid AND r.status = :st')
            ->setParameter('tid', (string)$tournamentId)
            ->setParameter('st', RegistrationStatus::CONFIRMED->value)
            ->getQuery()->getSingleScalarResult();
    }
    public function firstWaitingList(Uuid $tournamentId): ?Registration
    {
        return $this->em->getRepository(Registration::class)->findOneBy(
            ['tournamentId' => (string)$tournamentId, 'status' => RegistrationStatus::WAITING_LIST->value],
            ['registeredAt' => 'ASC']
        );
    }
    public function all(?string $tournamentId, ?string $status, array $allowedTournamentIds = []): array
    {
        $qb = $this->em->createQueryBuilder()->select('r')->from(Registration::class, 'r')
            ->orderBy('r.registeredAt', 'DESC');
        if ($tournamentId) {
            $qb->andWhere('r.tournamentId = :tid')->setParameter('tid', $tournamentId);
        } elseif ($allowedTournamentIds !== []) {
            $qb->andWhere('r.tournamentId IN (:tids)')->setParameter('tids', $allowedTournamentIds);
        }
        if ($status) $qb->andWhere('r.status = :st')->setParameter('st', $status);
        return $qb->getQuery()->getResult();
    }
}
