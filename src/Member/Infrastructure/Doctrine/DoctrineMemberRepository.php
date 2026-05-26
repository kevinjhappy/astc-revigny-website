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

    public function save(Member $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    public function remove(Member $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    public function get(Uuid $id): ?Member
    {
        return $this->em->getRepository(Member::class)->findOneBy(['id' => (string)$id]);
    }

    public function search(?string $query): array
    {
        $qb = $this->em->createQueryBuilder()->select('m')->from(Member::class, 'm')->orderBy('m.lastName', 'ASC');
        if ($query) {
            $qb->where('m.lastName LIKE :query OR m.firstName LIKE :query OR m.phone LIKE :query')
               ->setParameter('query', '%'.$query.'%');
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
