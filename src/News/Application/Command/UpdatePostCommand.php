<?php
namespace App\News\Application\Command;

final class UpdatePostCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $content,
    ) {}
}
