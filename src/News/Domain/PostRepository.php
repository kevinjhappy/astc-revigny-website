<?php
namespace App\News\Domain;

use App\Shared\Domain\ValueObject\Uuid;

interface PostRepository
{
    public function save(Post $post): void;
    public function get(Uuid $id): ?Post;
    public function delete(Uuid $id): void;
    public function all(): array;
    public function latestPublished(int $limit = 6): array;
}
