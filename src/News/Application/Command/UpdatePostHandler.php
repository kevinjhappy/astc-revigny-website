<?php

namespace App\News\Application\Command;

use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdatePostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(UpdatePostCommand $command): void
    {
        $post = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('Post not found');
        $post->update($command->title, $command->content);
        $this->repo->save($post);
    }
}
