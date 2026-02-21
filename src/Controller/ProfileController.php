<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Form\UserProfileType;
use App\Repository\LocataireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        Request $request,
        LocataireRepository $locataireRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Trouver le locataire associé à l'utilisateur
        $locataire = $locataireRepository->findOneBy(['user' => $user]);

        if (!$locataire) {
            // Si c'est un admin sans fiche locataire, on peut soit créer une erreur soit gérer
            // Pour l'instant on simule une fiche ou on redirige
            $this->addFlash('warning', 'Vous n\'avez pas encore de fiche locataire associée.');
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(UserProfileType::class, $locataire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du changement de mot de passe
            $currentPassword = $form->get('currentPassword')->getData();
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                if (!$currentPassword) {
                    $this->addFlash('error', 'Vous devez saisir votre mot de passe actuel pour le modifier.');
                    return $this->redirectToRoute('app_profile');
                }

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile');
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                $entityManager->persist($user);
            }

            // Gestion de l'email (synchro Locataire <-> User)
            $user->setEmail($locataire->getEmail());

            // Gestion de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('locataires_directory'),
                        $newFilename
                    );

                    if ($locataire->getPhoto()) {
                        $oldPath = $this->getParameter('locataires_directory') . '/' . $locataire->getPhoto();
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $locataire->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
            'locataire' => $locataire
        ]);
    }
}
