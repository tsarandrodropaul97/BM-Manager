<?php

namespace App\Form;

use App\Entity\Locataire;
use App\Entity\Operation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class OperationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'opération',
                'attr' => ['placeholder' => 'Ex: Organisation des déchets, Maintenance ascenseur...']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Déchets' => 'Déchets',
                    'Maintenance' => 'Maintenance',
                    'Organisation' => 'Organisation',
                    'Sinistre' => 'Sinistre',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('isGlobal', CheckboxType::class, [
                'label' => 'Diffuser à tous les locataires',
                'required' => false,
            ])
            ->add('targetLocataires', EntityType::class, [
                'class' => Locataire::class,
                'choice_label' => 'nomComplet',
                'multiple' => true,
                'required' => false,
                'label' => 'Cibler des locataires spécifiques',
                'help' => 'Laissez vide si l\'opération concerne tous les locataires',
                'attr' => [
                    'class' => 'select2 form-select',
                    'data-placeholder' => 'Sélectionner un ou plusieurs locataires...'
                ],
            ])
            ->add('files', FileType::class, [
                'label' => 'Documents & Photos',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'help' => 'Vous pouvez sélectionner plusieurs fichiers (Images ou PDF, max 10Mo)',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*,application/pdf'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }
}
