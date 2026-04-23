<?php
namespace App\Security\Domain;
interface AdminUserRepository
{
    public function save(AdminUser $u): void;
    public function findByEmail(string $email): ?AdminUser;
}
