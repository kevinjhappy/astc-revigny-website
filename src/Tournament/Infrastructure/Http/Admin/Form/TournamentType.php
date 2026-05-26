<?php

namespace App\Tournament\Infrastructure\Http\Admin\Form;

use App\Tournament\Domain\TournamentType as DomainType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank()]])
          ->add('startDate', DateType::class, ['label' => 'Début', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
          ->add('endDate', DateType::class, ['label' => 'Fin', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
          ->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => ['Ouvert' => DomainType::OPEN->value, 'Membres uniquement' => DomainType::MEMBERS_ONLY->value, 'Ten Up' => DomainType::TEN_UP->value]])
          ->add('maxParticipants', IntegerType::class, ['label' => 'Max participants', 'constraints' => [new Assert\Positive()]])
          ->add('description', TextareaType::class, [
              'label' => 'Description',
              'required' => false,
              'attr' => ['placeholder' => 'Ex : De N/C à 30/4 — Hommes, Femmes, Jeunes', 'rows' => 3],
          ]);
    }
}
