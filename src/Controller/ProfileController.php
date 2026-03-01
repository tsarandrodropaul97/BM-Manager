<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Form\AdminProfileType;
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

        // --- CAS ADMIN : pas de fiche locataire ---
        if ($this->isGranted('ROLE_ADMIN') && !$locataireRepository->findOneBy(['user' => $user])) {
            $form = $this->createForm(AdminProfileType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $currentPassword = $form->get('currentPassword')->getData();
                $plainPassword   = $form->get('plainPassword')->getData();

                if ($plainPassword) {
                    if (!$currentPassword) {
                        $this->addFlash('error', 'Vous devez saisir votre mot de passe actuel pour le modifier.');
                        return $this->redirectToRoute('app_profile');
                    }
                    if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                        $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                        return $this->redirectToRoute('app_profile');
                    }
                    $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                }

                $entityManager->flush();
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
                return $this->redirectToRoute('app_profile');
            }

            return $this->render('profile/admin.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
            ]);
        }

        // --- CAS LOCATAIRE ---
        $locataire = $locataireRepository->findOneBy(['user' => $user]);

        if (!$locataire) {
            $this->addFlash('warning', 'Vous n\'avez pas encore de fiche locataire associée.');
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(UserProfileType::class, $locataire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du changement de mot de passe
            $currentPassword = $form->get('currentPassword')->getData();
            $plainPassword   = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                if (!$currentPassword) {
                    $this->addFlash('error', 'Vous devez saisir votre mot de passe actuel pour le modifier.');
                    return $this->redirectToRoute('app_profile');
                }
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile');
                }
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $entityManager->persist($user);
            }

            // Synchronisation email Locataire <-> User
            $user->setEmail($locataire->getEmail());

            // Gestion de la photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = $this->uploadFile($photoFile, $slugger, $this->getParameter('locataires_directory'));
                if ($newFilename) {
                    $this->deleteOldFile($this->getParameter('locataires_directory'), $locataire->getPhoto());
                    $locataire->setPhoto($newFilename);
                }
            }

            // Gestion du CIN Recto
            $cinRectoFile = $form->get('cinRecto')->getData();
            if ($cinRectoFile) {
                $newFilename = $this->uploadFile($cinRectoFile, $slugger, $this->getParameter('locataires_directory'));
                if ($newFilename) {
                    $this->deleteOldFile($this->getParameter('locataires_directory'), $locataire->getCinRecto());
                    $locataire->setCinRecto($newFilename);
                }
            }

            // Gestion du CIN Verso
            $cinVersoFile = $form->get('cinVerso')->getData();
            if ($cinVersoFile) {
                $newFilename = $this->uploadFile($cinVersoFile, $slugger, $this->getParameter('locataires_directory'));
                if ($newFilename) {
                    $this->deleteOldFile($this->getParameter('locataires_directory'), $locataire->getCinVerso());
                    $locataire->setCinVerso($newFilename);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form'      => $form->createView(),
            'locataire' => $locataire,
        ]);
    }

    /**
     * Upload un fichier et retourne le nouveau nom de fichier.
     */
    private function uploadFile($file, SluggerInterface $slugger, string $directory): ?string
    {
        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
            $file->move($directory, $newFilename);
            return $newFilename;
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime un ancien fichier s'il existe.
     */
    private function deleteOldFile(string $directory, ?string $filename): void
    {
        if ($filename) {
            $oldPath = $directory . '/' . $filename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    }
}
