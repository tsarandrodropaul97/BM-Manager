<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        NotificationService $notificationService
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Utiliser le MDP saisi ou en générer un automatiquement
            $plainPassword = $form->get('plainPassword')->getData();
            if (!$plainPassword) {
                $plainPassword = $this->generateSecurePassword();
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de bienvenue avec les identifiants
            try {
                $notificationService->sendWelcomeEmail(
                    $user->getEmail(),
                    $plainPassword,
                    $user->getFullName()
                );
                $this->addFlash('success', sprintf(
                    'Utilisateur créé avec succès. Un email avec les identifiants a été envoyé à %s.',
                    $user->getEmail()
                ));
            } catch (\Exception $e) {
                $this->addFlash('warning', sprintf(
                    'Utilisateur créé mais l\'envoi de l\'email a échoué : %s. Mot de passe temporaire : <strong>%s</strong>',
                    $e->getMessage(),
                    $plainPassword
                ));
            }

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Empêcher de se supprimer soi-même
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_user_index');
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Génère un mot de passe sécurisé aléatoire de 12 caractères.
     * Mélange lettres majuscules/minuscules, chiffres et caractères spéciaux.
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $uppercase  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase  = 'abcdefghjkmnpqrstuvwxyz';
        $digits     = '23456789';
        $specials   = '@#!$%&*';

        // Garantir au moins 1 de chaque catégorie
        $password  = $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $specials[random_int(0, strlen($specials) - 1)];

        // Compléter le reste aléatoirement
        $all = $uppercase . $lowercase . $digits . $specials;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Mélanger pour éviter la prévisibilité de l'ordre
        return str_shuffle($password);
    }
}
