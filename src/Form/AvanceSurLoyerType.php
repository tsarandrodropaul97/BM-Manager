<?php

namespace App\Form;

use App\Entity\AvanceSurLoyer;
use App\Entity\Locataire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class AvanceSurLoyerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'];

        if ($isAdmin) {
            $builder->add('locataire', EntityType::class, [
                'class' => Locataire::class,
                'choice_label' => function (Locataire $locataire) {
                    return $locataire->getNomComplet() . ' (' . ($locataire->getBien() ? $locataire->getBien()->getDesignation() : 'Sans bien') . ')';
                },
                'placeholder' => 'Sélectionnez un locataire',
                'label' => 'Locataire concerné',
                'attr' => ['class' => 'form-select']
            ]);
        }

        $builder
            ->add('montantTotal', MoneyType::class, [
                'currency' => 'MGA',
                'label' => 'Montant versé au propriétaire',
                'attr' => ['placeholder' => 'Ex: 500000']
            ])
            ->add('dateAccord', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de réception par le propriétaire'
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Contexte (Ex: Demande exceptionnelle du propriétaire)',
                'attr' => ['rows' => 3, 'placeholder' => 'Précisez les raisons de cette avance...'],
                'required' => false
            ])
            ->add('document', FileType::class, [
                'label' => 'Preuve de la demande (Papier signé, reçu, etc.)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un document PDF ou une image valide.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvanceSurLoyer::class,
            'is_admin' => false,
        ]);
    }
}
