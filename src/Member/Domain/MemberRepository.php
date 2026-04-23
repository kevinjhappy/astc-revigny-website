<?php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

interface MemberRepository
{
    public function save(Member $member): void;
    public function remove(Member $member): void;
    public function get(Uuid $id): ?Member;
    /** @return Member[] */
    public function search(?string $query): array;
    public function findByLastNameAndPhone(string $lastName, PhoneNumber $phone): ?Member;
}
