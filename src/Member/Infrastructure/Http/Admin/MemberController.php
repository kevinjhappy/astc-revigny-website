<?php
namespace App\Member\Infrastructure\Http\Admin;

use App\Member\Application\Command\CreateMemberCommand;
use App\Member\Application\Command\CreateMemberHandler;
use App\Member\Application\Command\DeleteMemberCommand;
use App\Member\Application\Command\DeleteMemberHandler;
use App\Member\Application\Command\UpdateMemberCommand;
use App\Member\Application\Command\UpdateMemberHandler;
use App\Member\Domain\MemberRepository;
use App\Member\Infrastructure\Http\Admin\Form\MemberType;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/admin/members")]
#[IsGranted("ROLE_ADMIN")]
final class MemberController extends AbstractController
{
    #[Route("", name: "admin_member_list", methods: ["GET"])]
    public function list(Request $r, MemberRepository $repo): Response
    {
        return $this->render("admin/member/list.html.twig", [
            "members" => $repo->search($r->query->get("q")),
            "q" => $r->query->get("q", ""),
        ]);
    }

    #[Route("/new", name: "admin_member_new", methods: ["GET","POST"])]
    public function new(Request $r, CreateMemberHandler $h): Response
    {
        $form = $this->createForm(MemberType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new CreateMemberCommand($d["lastName"], $d["firstName"], $d["phone"], $d["email"] ?? null));
            $this->addFlash("success", "Membre créé");
            return $this->redirectToRoute("admin_member_list");
        }
        return $this->render("admin/member/new.html.twig", ["form" => $form]);
    }

    #[Route("/{id}/edit", name: "admin_member_edit", methods: ["GET","POST"])]
    public function edit(string $id, Request $r, MemberRepository $repo, UpdateMemberHandler $h): Response
    {
        $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(MemberType::class, [
            "lastName" => $m->lastName(), "firstName" => $m->firstName(),
            "phone" => (string)$m->phone(), "email" => $m->email() ? (string)$m->email() : null,
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdateMemberCommand($id, $d["lastName"], $d["firstName"], $d["phone"], $d["email"] ?? null));
            $this->addFlash("success", "Membre mis à jour");
            return $this->redirectToRoute("admin_member_list");
        }
        return $this->render("admin/member/edit.html.twig", ["form" => $form, "member" => $m]);
    }

    #[Route("/{id}", name: "admin_member_delete", methods: ["POST"])]
    public function delete(string $id, Request $r, DeleteMemberHandler $h): Response
    {
        if ($this->isCsrfTokenValid("del".$id, $r->request->get("_token"))) {
            $h(new DeleteMemberCommand($id));
            $this->addFlash("success", "Membre supprimé");
        }
        return $this->redirectToRoute("admin_member_list");
    }
}
