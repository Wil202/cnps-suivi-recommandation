<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Meeting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeetingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la séance',
                'attr' => ['placeholder' => 'Ex : Audit de la conformité des primes 2026'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de séance',
                'choices' => array_flip(Meeting::TYPES),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Meeting::STATUSES),
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'required' => false,
                'widget' => 'single_text', // un seul champ datetime-local (plus propre)
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Salle des Actes, ou Visioconférence (Teams)'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Ordre du jour / description',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meeting::class,
        ]);
    }
}
