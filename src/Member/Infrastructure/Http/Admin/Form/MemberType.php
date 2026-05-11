<?php
namespace App\Member\Infrastructure\Http\Admin\Form;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add('lastName', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('firstName', TextType::class, ['label' => 'Prénom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('phone', TextType::class, ['label' => 'Téléphone', 'constraints' => [new Assert\NotBlank()]])
          ->add('email', EmailType::class, ['label' => 'Email', 'required' => false])
          ->add('birthDate', TextType::class, [
              'label' => 'Date de naissance',
              'required' => false,
              'attr' => ['placeholder' => 'JJ/MM/AAAA'],
              'constraints' => [new Assert\Regex(['pattern' => '/^\d{2}\/\d{2}\/\d{4}$/', 'message' => 'Format attendu : JJ/MM/AAAA'])],
          ])
          ->add('membershipType', ChoiceType::class, [
              'label' => 'Type de cotisation',
              'required' => false,
              'choices' => array_combine(
                  array_map(fn($t) => $t->label(), MembershipType::cases()),
                  MembershipType::cases(),
              ),
              'placeholder' => '— Aucune cotisation —',
          ])
          ->add('subscriptionStatus', ChoiceType::class, [
              'label' => 'Statut paiement',
              'required' => false,
              'choices' => [
                  'En attente' => SubscriptionStatus::PENDING,
                  'Payé' => SubscriptionStatus::PAID,
              ],
              'placeholder' => '— Sélectionner —',
          ]);
    }
}
