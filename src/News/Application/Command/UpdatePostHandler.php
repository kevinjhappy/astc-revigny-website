<?php
namespace App\News\Application\Command;

use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdatePostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(UpdatePostCommand $c): void
    {
        $post = $this->repo->get(Uuid::fromString($c->id))
            ?? throw new \DomainException('Post not found');
        $post->update($c->title, $c->content);
        $this->repo->save($post);
    }
}
