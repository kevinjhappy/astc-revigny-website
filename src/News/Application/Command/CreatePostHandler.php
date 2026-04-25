<?php
namespace App\News\Application\Command;

use App\News\Domain\Post;
use App\News\Domain\PostRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class CreatePostHandler
{
    public function __construct(private PostRepository $repo) {}

    public function __invoke(CreatePostCommand $c): Uuid
    {
        $id = Uuid::generate();
        $this->repo->save(Post::create($id, $c->title, $c->content));
        return $id;
    }
}
