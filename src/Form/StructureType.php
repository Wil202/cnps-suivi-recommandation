<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Structure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['placeholder' => 'Ex : DSI, DRH, DCAI'],
                'help' => 'Code institutionnel unique, en majuscules.',
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé complet',
                'attr' => ['placeholder' => 'Ex : Direction des Systèmes d\'Information'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de structure',
                // On réutilise les constantes de l'entité : pas de valeur en dur ici
                'choices' => array_flip(Structure::TYPES),
            ])
            ->add('chiefEmail', TextType::class, [
                'label' => 'Email du chef (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'chef.structure@cnps.cm'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Mission et périmètre de la structure...'],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Structure active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Structure::class,
        ]);
    }
}
