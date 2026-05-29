<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Department;
use App\Entity\Structure;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Ex : Estelle'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Ex : NGO BIYIK'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email professionnel',
                'attr' => ['placeholder' => 'prenom.nom@cnps.cm'],
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : CNPS-8842-X'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle applicatif',
                'mapped' => false,
                'choices' => array_flip(User::ROLES_LABELS),
                'data' => $options['current_role'],
                'help' => 'Détermine les droits de l\'utilisateur dans l\'application.',
            ])
            ->add('structure', EntityType::class, [
                'label' => 'Structure de rattachement',
                'class' => Structure::class,
                'required' => false,
                'placeholder' => 'Aucune / à définir',
                'choice_label' => fn (Structure $s) => $s->getCode() . ' — ' . $s->getLabel(),
            ])
            ->add('department', EntityType::class, [
                'label' => 'Service de rattachement',
                'class' => Department::class,
                'required' => false,
                'placeholder' => 'Aucun / à définir',
                'choice_label' => fn (Department $d) => $d->getCode() . ' — ' . $d->getLabel(),
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'attr' => ['placeholder' => '••••••••', 'autocomplete' => 'new-password'],
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire à la création.']),
                    new Length(['min' => 4, 'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères.']),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'current_role' => User::ROLE_AGENT,
        ]);
    }
}