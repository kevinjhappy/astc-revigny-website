<?php
namespace App\News\Application\Command;

final class CreatePostCommand
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
    ) {}
}
