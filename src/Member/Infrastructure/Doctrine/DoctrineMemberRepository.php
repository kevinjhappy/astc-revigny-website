<?php
namespace App\Member\Infrastructure\Doctrine;

use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMemberRepository implements MemberRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Member $m): void { $this->em->persist($m); $this->em->flush(); }
    public function remove(Member $m): void { $this->em->remove($m); $this->em->flush(); }

    public function get(Uuid $id): ?Member
    {
        return $this->em->getRepository(Member::class)->findOneBy(['id' => (string)$id]);
    }

    public function search(?string $q): array
    {
        $qb = $this->em->createQueryBuilder()->select('m')->from(Member::class, 'm')->orderBy('m.lastName', 'ASC');
        if ($q) {
            $qb->where('m.lastName LIKE :q OR m.firstName LIKE :q OR m.phone LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        return $qb->getQuery()->getResult();
    }

    public function findByLastNameAndPhone(string $lastName, PhoneNumber $phone): ?Member
    {
        return $this->em->getRepository(Member::class)->findOneBy([
            'lastName' => $lastName,
            'phone' => (string)$phone,
        ]);
    }
}
