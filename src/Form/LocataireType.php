<?php

namespace App\Form;

use App\Entity\Biens;
use App\Entity\Locataire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class LocataireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Entrez le prénom'],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('lieuNaissance', TextType::class, [
                'label' => 'Lieu de naissance',
                'attr' => ['placeholder' => 'ex: Antananarivo'],
            ])
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité',
                'attr' => ['placeholder' => 'ex: Malagasy'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'ex: 034 00 000 00'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'ex: locataire@exemple.com'],
            ])
            ->add('adresseActuelle', TextareaType::class, [
                'label' => 'Adresse actuelle',
                'attr' => ['rows' => 2, 'placeholder' => 'Adresse complète actuelle'],
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'attr' => ['placeholder' => 'ex: Comptable'],
            ])
            ->add('employeur', TextType::class, [
                'label' => 'Employeur',
                'attr' => ['placeholder' => 'Nom de l\'entreprise'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'En attente' => 'en_attente',
                    'Préavis' => 'preavis',
                ],
            ])
            ->add('dateEntree', DateType::class, [
                'label' => 'Date d\'entrée',
                'widget' => 'single_text',
            ])
            ->add('bien', EntityType::class, [
                'class' => Biens::class,
                'choice_label' => 'designation',
                'label' => 'Bien loué',
                'placeholder' => 'Sélectionnez un bien',
                'required' => false,
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('cinRecto', FileType::class, [
                'label' => 'CIN Recto',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier valide (JPG, PNG, WEBP, PDF)',
                    ])
                ],
            ])
            ->add('cinVerso', FileType::class, [
                'label' => 'CIN Verso',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier valide (JPG, PNG, WEBP, PDF)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Locataire::class,
        ]);
    }
}
