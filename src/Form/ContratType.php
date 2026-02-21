<?php

namespace App\Form;

use App\Entity\Biens;
use App\Entity\Contrat;
use App\Entity\Locataire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence du contrat',
                'attr' => ['placeholder' => 'ex: BAIL-2024-001'],
            ])
            ->add('locataire', EntityType::class, [
                'class' => Locataire::class,
                'choice_label' => 'nomComplet',
                'label' => 'Locataire',
                'placeholder' => 'Choisir un locataire',
            ])
            ->add('bien', EntityType::class, [
                'class' => Biens::class,
                'choice_label' => 'designation',
                'label' => 'Bien immobilier',
                'placeholder' => 'Choisir un bien',
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin (optionnel)',
                'required' => false,
            ])
            ->add('loyerHorsCharges', MoneyType::class, [
                'currency' => 'MGA',
                'label' => 'Loyer Hors Charges',
            ])
            ->add('charges', MoneyType::class, [
                'currency' => 'MGA',
                'label' => 'Provisions sur charges',
                'required' => false,
            ])
            ->add('depotGarantie', MoneyType::class, [
                'currency' => 'MGA',
                'label' => 'Dépôt de garantie (Caution)',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'brouillon',
                    'Actif' => 'actif',
                    'Terminé' => 'termine',
                    'Résilié' => 'resilie',
                ],
                'label' => 'Statut du contrat',
            ])
            ->add('documentPdf', FileType::class, [
                'label' => 'Scan du contrat signé (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un document PDF valide',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
        ]);
    }
}
