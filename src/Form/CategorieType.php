<?php

namespace App\Form;

use App\Constant\TypeCategorie;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'nom'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom de la catégorie est obligatoire']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'help' => 'Nom unique pour identifier la catégorie'
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'description',
                    'rows' => 3,
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            ->add('couleur', ColorType::class, [
                'label' => 'Couleur de la catégorie',
                'required' => true,
                'data' => '#0d6efd',
                'attr' => [
                    'class' => 'form-control form-control-color me-3',
                    'id' => 'couleur',
                    'style' => 'width: 60px; height: 40px;'
                ],
                'help' => 'Cette couleur sera utilisée pour identifier visuellement la catégorie'
            ])

            ->add('icone', ChoiceType::class, [
                'label' => 'Icône',
                'required' => true,
                'data' => 'fa-home',
                'choices' => [
                    'Maison (fa-home)' => 'fa-home',
                    'Immeuble (fa-building)' => 'fa-building',
                    'Commerce (fa-store)' => 'fa-store',
                    'Industrie (fa-industry)' => 'fa-industry',
                    'Entrepôt (fa-warehouse)' => 'fa-warehouse',
                    'Garage (fa-car)' => 'fa-car',
                    'Terrain (fa-tree)' => 'fa-tree',
                    'Hôtel (fa-hotel)' => 'fa-hotel',
                    'Cabinet (fa-clinic-medical)' => 'fa-clinic-medical'
                ],
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'icone'
                ]
            ])

            ->add('statut', ChoiceType::class, [
                'label' => 'Statut de la catégorie',
                'required' => true,
                'data' => 'active',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Brouillon' => 'brouillon'
                ],
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'statut'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
            'attr' => [
                'id' => 'formulaireCategorie',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}
