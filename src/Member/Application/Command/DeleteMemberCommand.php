<?php
namespace App\Member\Application\Command;

final class DeleteMemberCommand
{
    public function __construct(public readonly string $id) {}
}
