<?php

namespace App\Form;

use App\Entity\Biens;
use App\Entity\Categorie;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;  // ← Important !
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BiensType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Section 1: Informations générales
            ->add('designation', TextType::class, [
                'label' => 'Désignation du bien',
                'attr' => [
                    'placeholder' => 'ex: Appartement T3 centre-ville',
                ],
                'required' => true,
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence interne',
                'attr' => [
                    'placeholder' => 'Auto-généré (ex: BM-0001)',
                ],
                'required' => false,
                'disabled' => true,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Vacant' => 'vacant',
                    'Occupé' => 'occupe',
                    'En travaux' => 'travaux',
                    'En maintenance' => 'maintenance',
                ],
                'placeholder' => 'Sélectionnez le statut',
                'required' => true,
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom', // Changez selon votre entité
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez la catégorie',
                'required' => true,
            ])

            // Section 2: Adresse et localisation
            ->add('adresse', TextType::class, [
                'label' => 'Adresse complète',
                'attr' => [
                    'placeholder' => 'Numéro, rue, avenue...',
                ],
                'required' => true,
            ])
            ->add('code_postal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'placeholder' => '75001',
                    'maxlength' => 5,
                ],
                'required' => true,
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Paris',
                ],
                'required' => true,
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'placeholder' => 'France',
                ],
                'required' => true,
            ])
            ->add('secteur', TextType::class, [
                'label' => 'Quartier/Secteur',
                'attr' => [
                    'placeholder' => 'Centre-ville, Belleville...',
                ],
                'required' => true,
            ])

            // Section 3: Caractéristiques techniques
            ->add('surface_habitable', NumberType::class, [
                'label' => 'Surface habitable (m²)',
                'attr' => [
                    'placeholder' => '75',
                    'step' => '0.01',
                ],
                'required' => true,
            ])
            ->add('surface_total', NumberType::class, [
                'label' => 'Surface totale (m²)',
                'attr' => [
                    'placeholder' => '85',
                    'step' => '0.01',
                ],
                'required' => true,
            ])
            ->add('nbr_piece', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'attr' => [
                    'placeholder' => '3',
                    'min' => 1,
                ],
                'required' => true,
            ])
            ->add('nbr_chambre', IntegerType::class, [
                'label' => 'Nombre de chambres',
                'attr' => [
                    'placeholder' => '2',
                    'min' => 0,
                ],
                'required' => true,
            ])
            ->add('salle_bain', IntegerType::class, [
                'label' => 'Salles de bain',
                'attr' => [
                    'placeholder' => '1',
                    'min' => 0,
                ],
                'required' => true,
            ])
            ->add('wc', IntegerType::class, [
                'label' => 'WC séparés',
                'attr' => [
                    'placeholder' => '1',
                    'min' => 0,
                ],
                'required' => true,
            ])
            ->add('etage', IntegerType::class, [
                'label' => 'Étage',
                'attr' => [
                    'placeholder' => '3',
                    'min' => 0,
                ],
                'required' => true,
            ])
            ->add('ascenseur', ChoiceType::class, [
                'label' => 'Ascenseur',
                'choices' => [
                    'Non spécifié' => null,
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'placeholder' => 'Sélectionnez',
                'required' => false,
            ])
            ->add('balcon', ChoiceType::class, [
                'label' => 'Balcon/Terrasse',
                'choices' => [
                    'Aucun' => null,
                    'Balcon' => 'balcon',
                    'Terrasse' => 'terrasse',
                    'Loggia' => 'loggia',
                    'Jardin privatif' => 'jardin',
                ],
                'placeholder' => 'Sélectionnez',
                'required' => true,
            ])

            // Section 4: Équipements
            ->add('typechauffage', ChoiceType::class, [
                'label' => 'Type de chauffage',
                'choices' => [
                    'Individuel gaz' => 'individuel_gaz',
                    'Individuel électrique' => 'individuel_electrique',
                    'Collectif' => 'collectif',
                    'Climatisation réversible' => 'climatisation',
                    'Poêle' => 'poele',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Sélectionnez',
                'required' => true,
            ])
            ->add('eau', ChoiceType::class, [
                'label' => 'Eau chaude',
                'choices' => [
                    'Individuelle' => 'individuelle',
                    'Collective' => 'collective',
                    'Électrique' => 'electrique',
                    'Gaz' => 'gaz',
                ],
                'placeholder' => 'Sélectionnez',
                'required' => true,
            ])
            ->add('cuisine', ChoiceType::class, [
                'label' => 'Cuisine',
                'choices' => [
                    'Équipée' => 'equipee',
                    'Aménagée' => 'amenagee',
                    'Non équipée' => 'non_equipee',
                    'Kitchenette' => 'kitchenette',
                ],
                'placeholder' => 'Sélectionnez',
                'required' => true,
            ])

            // Équipements (checkboxes)
            ->add('is_parking', CheckboxType::class, [
                'label' => 'Place de parking',
                'required' => false,
            ])
            ->add('is_garage', CheckboxType::class, [
                'label' => 'Garage',
                'required' => false,
            ])
            ->add('is_interphone', CheckboxType::class, [
                'label' => 'Interphone',
                'required' => false,
            ])
            ->add('is_gardien', CheckboxType::class, [
                'label' => 'Gardien',
                'required' => false,
            ])
            ->add('is_connexion', CheckboxType::class, [
                'label' => 'Connexion internet',
                'required' => false,
            ])
            ->add('is_chemine', CheckboxType::class, [
                'label' => 'Cheminée',
                'required' => false,
            ])
            ->add('is_jardin', CheckboxType::class, [
                'label' => 'Jardin',
                'required' => false,
            ])
            ->add('is_meuble', CheckboxType::class, [
                'label' => 'Meublé',
                'required' => false,
            ])

            // Section 5: Notes et observations
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description complète du bien, ses atouts, particularités...',
                ],
                'required' => true,
            ])
            ->add('notes_interne', TextareaType::class, [
                'label' => 'Notes internes',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Notes privées, points d\'attention, historique...',
                ],
                'required' => true,
            ])
            ->add('points_attention', TextareaType::class, [
                'label' => 'Points d\'attention',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Éléments à surveiller, défauts connus...',
                ],
                'required' => true,
            ])
            ->add('image', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Image principale du bien',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Biens::class,
        ]);
    }
}
