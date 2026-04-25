<?php
namespace App\Tests\News\Domain;

use App\News\Domain\Post;
use App\News\Domain\PostStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    private function make(): Post
    {
        return Post::create(Uuid::generate(), 'Titre test', 'Contenu du post.');
    }

    public function test_starts_draft(): void
    {
        self::assertSame(PostStatus::DRAFT, $this->make()->status());
    }

    public function test_published_at_null_when_draft(): void
    {
        self::assertNull($this->make()->publishedAt());
    }

    public function test_publish_sets_status_and_date(): void
    {
        $p = $this->make();
        $p->publish();
        self::assertSame(PostStatus::PUBLISHED, $p->status());
        self::assertInstanceOf(\DateTimeImmutable::class, $p->publishedAt());
    }

    public function test_cannot_publish_twice(): void
    {
        $p = $this->make();
        $p->publish();
        $this->expectException(\DomainException::class);
        $p->publish();
    }

    public function test_unpublish_clears_status_and_date(): void
    {
        $p = $this->make();
        $p->publish();
        $p->unpublish();
        self::assertSame(PostStatus::DRAFT, $p->status());
        self::assertNull($p->publishedAt());
    }

    public function test_cannot_unpublish_draft(): void
    {
        $this->expectException(\DomainException::class);
        $this->make()->unpublish();
    }

    public function test_update_changes_title_and_content(): void
    {
        $p = $this->make();
        $p->update('Nouveau titre', 'Nouveau contenu.');
        self::assertSame('Nouveau titre', $p->title());
        self::assertSame('Nouveau contenu.', $p->content());
    }
}
