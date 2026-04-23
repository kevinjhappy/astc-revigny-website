<?php
namespace App\Tournament\Infrastructure\Http\Admin\Form;
use App\Tournament\Domain\TournamentType as DomainType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
final class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add('name', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank()]])
          ->add('startDate', DateTimeType::class, ['label' => 'Début', 'widget' => 'single_text'])
          ->add('endDate', DateTimeType::class, ['label' => 'Fin', 'widget' => 'single_text'])
          ->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => ['Ouvert' => DomainType::OPEN->value, 'Membres uniquement' => DomainType::MEMBERS_ONLY->value]])
          ->add('maxParticipants', IntegerType::class, ['label' => 'Max participants', 'constraints' => [new Assert\Positive()]])
          ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false]);
    }
}
