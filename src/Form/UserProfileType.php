<?php

namespace App\Form;

use App\Entity\Locataire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('adresseActuelle', TextareaType::class, [
                'label' => 'Adresse actuelle',
                'attr' => ['rows' => 2],
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
            ])
            ->add('employeur', TextType::class, [
                'label' => 'Employeur',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
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
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP ou PDF)',
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
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP ou PDF)',
                    ])
                ],
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Obligatoire pour changer de mot de passe'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => 'Nouveau mot de passe']
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['placeholder' => 'Confirmer le nouveau mot de passe']
                ],
                'invalid_message' => 'Les nouveaux mots de passe ne correspondent pas.',
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
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
