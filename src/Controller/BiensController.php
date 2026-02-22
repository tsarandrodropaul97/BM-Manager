<?php

namespace App\Controller;

use App\Entity\Biens;
use App\Form\BiensType;
use App\Repository\BiensRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/biens')]
final class BiensController extends AbstractController
{
    #[Route(name: 'app_biens_index', methods: ['GET'])]
    public function index(Request $request, BiensRepository $biensRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $ville = $request->query->get('ville');
        $limit = $request->query->getInt('limit', 10);
        $viewMode = $request->query->get('viewMode', 'grid');

        $queryBuilder = $biensRepository->getFilteredQueryBuilder($search, $type, $statut, $ville);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        $totalBiens = $biensRepository->count([]);
        $occupes = $biensRepository->count(['statut' => 'occupe']);
        $vacants = $biensRepository->count(['statut' => 'vacant']);
        $enTravaux = $biensRepository->count(['statut' => 'travaux']);

        return $this->render('biens/index.html.twig', [
            'pagination' => $pagination,
            'viewMode' => $viewMode,
            'stats' => [
                'total' => $totalBiens,
                'occupes' => $occupes,
                'vacants' => $vacants,
                'enTravaux' => $enTravaux
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'statut' => $statut,
                'ville' => $ville,
                'limit' => $limit
            ]
        ]);
    }

    #[Route('/export', name: 'app_biens_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(Request $request, BiensRepository $biensRepository): StreamedResponse
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $ville = $request->query->get('ville');

        $queryBuilder = $biensRepository->getFilteredQueryBuilder($search, $type, $statut, $ville);
        $biens = $queryBuilder->getQuery()->getResult();

        $response = new StreamedResponse(function () use ($biens) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['Designation', 'Reference', 'Categorie', 'Statut', 'Ville', 'Surface Habitable', 'Pieces']);

            foreach ($biens as $bien) {
                fputcsv($handle, [
                    $bien->getDesignation(),
                    $bien->getReference(),
                    $bien->getCategorie() ? $bien->getCategorie()->getNom() : '',
                    $bien->getStatut(),
                    $bien->getVille(),
                    $bien->getSurfaceHabitable(),
                    $bien->getNbrPiece()
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="biens_export_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_biens_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, BiensRepository $biensRepository, SluggerInterface $slugger): Response
    {
        $bien = new Biens();
        $form = $this->createForm(BiensType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Génération de la référence automatique
            $lastBien = $biensRepository->findOneBy([], ['id' => 'DESC']);
            $nextNumber = 1;

            if ($lastBien && $lastBien->getReference()) {
                if (preg_match('/BM-(\d+)/', $lastBien->getReference(), $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                } else {
                    $nextNumber = $lastBien->getId() + 1;
                }
            } elseif ($lastBien) {
                $nextNumber = $lastBien->getId() + 1;
            }

            $bien->setReference('BM-' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT));

            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('biens_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $bien->setImage($newFilename);
            }

            $entityManager->persist($bien);
            $entityManager->flush();

            return $this->redirectToRoute('app_biens_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('biens/new.html.twig', [
            'bien' => $bien,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_biens_show', methods: ['GET'])]
    public function show(Biens $bien): Response
    {
        return $this->render('biens/show.html.twig', [
            'bien' => $bien,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_biens_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Biens $bien, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BiensType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('biens_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception
                }

                // Supprimer l'ancienne image si elle existe
                if ($bien->getImage()) {
                    $oldImagePath = $this->getParameter('biens_images_directory') . '/' . $bien->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $bien->setImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_biens_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('biens/edit.html.twig', [
            'bien' => $bien,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_biens_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Biens $bien, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $bien->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($bien);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_biens_index', [], Response::HTTP_SEE_OTHER);
    }
}
