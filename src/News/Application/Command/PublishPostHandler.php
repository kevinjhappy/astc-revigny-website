<?php

namespace App\News\Application\Command;

use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class PublishPostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(PublishPostCommand $command): void
    {
        $post = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('Post not found');
        $post->publish();
        $this->repo->save($post);
    }
}
