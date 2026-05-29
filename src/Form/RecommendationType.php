<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Meeting;
use App\Entity\Recommendation;
use App\Entity\Structure;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecommendationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé de la recommandation',
                'attr' => ['placeholder' => 'Ex : Mettre à jour la procédure de validation des primes'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Recommendation::STATUSES),
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => array_flip(Recommendation::PRIORITIES),
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Échéance',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('meeting', EntityType::class, [
                'label' => 'Séance d\'origine',
                'class' => Meeting::class,
                'required' => false,
                'placeholder' => 'Aucune séance liée',
                'choice_label' => fn (Meeting $m) => $m->getReference() . ' — ' . $m->getTitle(),
            ])
            ->add('assignedStructure', EntityType::class, [
                'label' => 'Structure affectée',
                'class' => Structure::class,
                'required' => false,
                'placeholder' => 'Non affectée',
                'choice_label' => fn (Structure $s) => $s->getCode() . ' — ' . $s->getLabel(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recommendation::class,
        ]);
    }
}
