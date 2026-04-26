<?php
namespace App\News\Infrastructure\Doctrine;

use App\News\Domain\Post;
use App\News\Domain\PostRepository;
use App\News\Domain\PostStatus;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePostRepository implements PostRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Post $post): void
    {
        $this->em->persist($post);
        $this->em->flush();
    }

    public function get(Uuid $id): ?Post
    {
        return $this->em->getRepository(Post::class)
            ->findOneBy(['id' => (string)$id]);
    }

    public function delete(Uuid $id): void
    {
        $post = $this->get($id);
        if ($post !== null) {
            $this->em->remove($post);
            $this->em->flush();
        }
    }

    public function all(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Post::class, 'p')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function latestPublished(int $limit = 6): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Post::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', PostStatus::PUBLISHED->value)
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
