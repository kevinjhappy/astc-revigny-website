<?php
namespace App\News\Application\Command;

final class UnpublishPostCommand
{
    public function __construct(public readonly string $id) {}
}
