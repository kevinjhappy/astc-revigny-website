<?php
namespace App\News\Infrastructure\Http\Admin;

use App\News\Application\Command\CreatePostCommand;
use App\News\Application\Command\CreatePostHandler;
use App\News\Application\Command\DeletePostCommand;
use App\News\Application\Command\DeletePostHandler;
use App\News\Application\Command\PublishPostCommand;
use App\News\Application\Command\PublishPostHandler;
use App\News\Application\Command\UnpublishPostCommand;
use App\News\Application\Command\UnpublishPostHandler;
use App\News\Application\Command\UpdatePostCommand;
use App\News\Application\Command\UpdatePostHandler;
use App\News\Domain\PostRepository;
use App\News\Infrastructure\Http\Admin\Form\PostType;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/posts')]
#[IsGranted('ROLE_ADMIN')]
final class PostController extends AbstractController
{
    #[Route('', name: 'admin_post_list', methods: ['GET'])]
    public function list(PostRepository $repo): Response
    {
        return $this->render('admin/post/list.html.twig', ['posts' => $repo->all()]);
    }

    #[Route('/new', name: 'admin_post_new', methods: ['GET', 'POST'])]
    public function new(Request $r, CreatePostHandler $h): Response
    {
        $form = $this->createForm(PostType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new CreatePostCommand($d['title'], $d['content']));
            $this->addFlash('success', 'Actualité créée');
            return $this->redirectToRoute('admin_post_list');
        }
        return $this->render('admin/post/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'admin_post_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $r, PostRepository $repo, UpdatePostHandler $h): Response
    {
        $post = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(PostType::class, [
            'title' => $post->title(),
            'content' => $post->content(),
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdatePostCommand($id, $d['title'], $d['content']));
            $this->addFlash('success', 'Actualité mise à jour');
            return $this->redirectToRoute('admin_post_list');
        }
        return $this->render('admin/post/edit.html.twig', ['form' => $form, 'post' => $post]);
    }

    #[Route('/{id}/publish', name: 'admin_post_publish', methods: ['POST'])]
    public function publish(string $id, Request $r, PublishPostHandler $h): Response
    {
        if ($this->isCsrfTokenValid('pub' . $id, $r->request->get('_token'))) {
            $h(new PublishPostCommand($id));
            $this->addFlash('success', 'Actualité publiée');
        }
        return $this->redirectToRoute('admin_post_list');
    }

    #[Route('/{id}/unpublish', name: 'admin_post_unpublish', methods: ['POST'])]
    public function unpublish(string $id, Request $r, UnpublishPostHandler $h): Response
    {
        if ($this->isCsrfTokenValid('unp' . $id, $r->request->get('_token'))) {
            $h(new UnpublishPostCommand($id));
            $this->addFlash('success', 'Actualité repassée en brouillon');
        }
        return $this->redirectToRoute('admin_post_list');
    }

    #[Route('/{id}/delete', name: 'admin_post_delete', methods: ['POST'])]
    public function delete(string $id, Request $r, DeletePostHandler $h): Response
    {
        if ($this->isCsrfTokenValid('del' . $id, $r->request->get('_token'))) {
            $h(new DeletePostCommand($id));
            $this->addFlash('success', 'Actualité supprimée');
        }
        return $this->redirectToRoute('admin_post_list');
    }
}
