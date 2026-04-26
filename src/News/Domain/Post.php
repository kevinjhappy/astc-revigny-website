<?php
namespace App\News\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'news_posts')]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt;

    private function __construct(Uuid $id, string $title, string $content)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->status = PostStatus::DRAFT->value;
        $this->publishedAt = null;
    }

    public static function create(Uuid $id, string $title, string $content): self
    {
        return new self($id, $title, $content);
    }

    public function update(string $title, string $content): void
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function publish(): void
    {
        if ($this->status !== PostStatus::DRAFT->value) {
            throw new \DomainException('only DRAFT can be published');
        }
        $this->status = PostStatus::PUBLISHED->value;
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function unpublish(): void
    {
        if ($this->status !== PostStatus::PUBLISHED->value) {
            throw new \DomainException('only PUBLISHED can be unpublished');
        }
        $this->status = PostStatus::DRAFT->value;
        $this->publishedAt = null;
    }

    public function id(): Uuid { return $this->id; }
    public function title(): string { return $this->title; }
    public function content(): string { return $this->content; }
    public function status(): PostStatus { return PostStatus::from($this->status); }
    public function publishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
}
