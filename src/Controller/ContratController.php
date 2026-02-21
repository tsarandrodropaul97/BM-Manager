<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/contrats')]
class ContratController extends AbstractController
{
    #[Route('', name: 'app_contrat_index', methods: ['GET'])]
    public function index(ContratRepository $contratRepository): Response
    {
        $limitDate = new \DateTime('+3 months');
        $expiringSoon = $contratRepository->findExpiringSoon($limitDate);

        return $this->render('contrat/index.html.twig', [
            'contrats' => $contratRepository->findAll(),
            'expiringSoon' => $expiringSoon,
        ]);
    }

    #[Route('/new', name: 'app_contrat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $contrat = new Contrat();
        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pdfFile = $form->get('documentPdf')->getData();

            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('contrats_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du contrat.');
                }

                $contrat->setDocumentPdf($newFilename);
            }

            // Automatisme : Si contrat actif, mettre à jour le statut du bien
            if ($contrat->getStatut() === 'actif') {
                $bien = $contrat->getBien();
                $bien->setStatut('occupe');

                // Lier aussi le bien au locataire directement si ce n'est pas déjà fait
                $locataire = $contrat->getLocataire();
                $locataire->setBien($bien);

                $entityManager->persist($bien);
                $entityManager->persist($locataire);
            }

            $entityManager->persist($contrat);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat créé avec succès.');
            return $this->redirectToRoute('app_contrat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contrat/new.html.twig', [
            'contrat' => $contrat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_show', methods: ['GET'])]
    public function show(Contrat $contrat): Response
    {
        return $this->render('contrat/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contrat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contrat $contrat, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pdfFile = $form->get('documentPdf')->getData();

            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('contrats_directory'),
                        $newFilename
                    );
                    $contrat->setDocumentPdf($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Contrat mis à jour avec succès.');
            return $this->redirectToRoute('app_contrat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contrat/edit.html.twig', [
            'contrat' => $contrat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_delete', methods: ['POST'])]
    public function delete(Request $request, Contrat $contrat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contrat->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contrat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contrat_index', [], Response::HTTP_SEE_OTHER);
    }
}
