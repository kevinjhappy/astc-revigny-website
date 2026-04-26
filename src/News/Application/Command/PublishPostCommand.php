<?php
namespace App\News\Application\Command;

final class PublishPostCommand
{
    public function __construct(public readonly string $id) {}
}
