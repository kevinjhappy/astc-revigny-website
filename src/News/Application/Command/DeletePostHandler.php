<?php
namespace App\News\Application\Command;

use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeletePostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(DeletePostCommand $c): void
    {
        $this->repo->delete(Uuid::fromString($c->id));
    }
}
