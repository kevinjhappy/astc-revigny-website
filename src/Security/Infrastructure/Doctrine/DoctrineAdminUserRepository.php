<?php

namespace App\Security\Infrastructure\Doctrine;

use App\Security\Domain\AdminUser;
use App\Security\Domain\AdminUserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

final class DoctrineAdminUserRepository extends ServiceEntityRepository implements AdminUserRepository, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminUser::class);
    }

    public function save(AdminUser $adminUser): void
    {
        $this->getEntityManager()->persist($adminUser);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?AdminUser
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function loadUserByIdentifier(string $identifier): ?AdminUser
    {
        return $this->findByEmail($identifier);
    }
}
