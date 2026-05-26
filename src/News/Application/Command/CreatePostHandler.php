<?php

namespace App\News\Application\Command;

use App\News\Domain\Post;
use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class CreatePostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(CreatePostCommand $command): Uuid
    {
        $id = Uuid::generate();
        $this->repo->save(Post::create($id, $command->title, $command->content));

        return $id;
    }
}
