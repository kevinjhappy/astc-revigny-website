<?php
namespace App\Member\Infrastructure\Http\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add("lastName", TextType::class, ["label" => "Nom", "constraints" => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add("firstName", TextType::class, ["label" => "Prénom", "constraints" => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add("phone", TextType::class, ["label" => "Téléphone", "constraints" => [new Assert\NotBlank()]])
          ->add("email", EmailType::class, ["label" => "Email", "required" => false]);
    }
}
