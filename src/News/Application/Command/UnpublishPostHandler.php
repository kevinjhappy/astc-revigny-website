<?php
namespace App\News\Application\Command;

use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UnpublishPostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(UnpublishPostCommand $command): void
    {
        $post = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('Post not found');
        $post->unpublish();
        $this->repo->save($post);
    }
}
