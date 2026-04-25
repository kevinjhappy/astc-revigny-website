<?php
namespace App\News\Application\Command;

final class DeletePostCommand
{
    public function __construct(public readonly string $id) {}
}
