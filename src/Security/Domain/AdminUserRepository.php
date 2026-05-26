<?php

namespace App\Security\Domain;

interface AdminUserRepository
{
    public function save(AdminUser $adminUser): void;
    public function findByEmail(string $email): ?AdminUser;
}
