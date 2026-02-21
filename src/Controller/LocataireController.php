<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Form\LocataireType;
use App\Repository\LocataireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/locataires')]
final class LocataireController extends AbstractController
{
    #[Route(name: 'app_locataire_index', methods: ['GET'])]
    public function index(Request $request, LocataireRepository $locataireRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $ville = $request->query->get('ville');
        $dateEntree = $request->query->get('dateEntree');
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $locataireRepository->getFilteredQueryBuilder($search, $statut, $ville, $dateEntree);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        return $this->render('locataire/index.html.twig', [
            'pagination' => $pagination,
            'filters' => [
                'search' => $search,
                'statut' => $statut,
                'ville' => $ville,
                'dateEntree' => $dateEntree,
                'limit' => $limit
            ]
        ]);
    }

    #[Route('/new', name: 'app_locataire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        $locataire = new Locataire();
        $form = $this->createForm(LocataireType::class, $locataire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUploads($form, $locataire, $slugger);

            // Création automatique du compte utilisateur (si n'existe pas déjà)
            $existingUser = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $locataire->getEmail()]);

            if (!$existingUser) {
                $user = new \App\Entity\User();
                $user->setEmail($locataire->getEmail());
                $user->setRoles(['ROLE_USER']);
                $hashedPassword = $passwordHasher->hashPassword($user, '123456789');
                $user->setPassword($hashedPassword);

                $locataire->setUser($user);
                $entityManager->persist($user);
            } else {
                $locataire->setUser($existingUser);
            }

            $entityManager->persist($locataire);
            $entityManager->flush();

            return $this->redirectToRoute('app_locataire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('locataire/new.html.twig', [
            'locataire' => $locataire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_locataire_show', methods: ['GET'])]
    public function show(Locataire $locataire): Response
    {
        return $this->render('locataire/show.html.twig', [
            'locataire' => $locataire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_locataire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Locataire $locataire, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(LocataireType::class, $locataire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUploads($form, $locataire, $slugger);

            $entityManager->flush();

            return $this->redirectToRoute('app_locataire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('locataire/edit.html.twig', [
            'locataire' => $locataire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_locataire_delete', methods: ['POST'])]
    public function delete(Request $request, Locataire $locataire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $locataire->getId(), $request->getPayload()->getString('_token'))) {
            // Optionnel : supprimer les fichiers associés
            $this->removeFiles($locataire);

            $entityManager->remove($locataire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_locataire_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleFileUploads($form, Locataire $locataire, SluggerInterface $slugger): void
    {
        $files = [
            'photo' => 'setPhoto',
            'cinRecto' => 'setCinRecto',
            'cinVerso' => 'setCinVerso'
        ];

        foreach ($files as $fieldName => $setter) {
            $file = $form->get($fieldName)->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('locataires_directory'),
                        $newFilename
                    );

                    // Supprimer l'ancien fichier si existant
                    $getter = 'get' . ucfirst($fieldName);
                    $oldFile = $locataire->$getter();
                    if ($oldFile) {
                        $oldFilePath = $this->getParameter('locataires_directory') . '/' . $oldFile;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }

                    $locataire->$setter($newFilename);
                } catch (FileException $e) {
                    // Log error
                }
            }
        }
    }

    private function removeFiles(Locataire $locataire): void
    {
        $files = [$locataire->getPhoto(), $locataire->getCinRecto(), $locataire->getCinVerso()];
        foreach ($files as $file) {
            if ($file) {
                $filePath = $this->getParameter('locataires_directory') . '/' . $file;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
}
