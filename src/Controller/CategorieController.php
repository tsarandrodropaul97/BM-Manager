<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    #[Route('/categorie', name: 'app_categorie')]
    public function index(): Response
    {
        return $this->render('categorie/index.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }

    #[Route('/categorie/ajout', name: 'app_categorie_ajout')]
    public function ajout(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);

        // === Gestion AJAX ===
        if ($request->isXmlHttpRequest()) {
            return $this->handleAjaxForm($form, $categorie, $entityManager);
        }

        return $this->render('categorie/ajout.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie
        ]);
    }

    // Méthode séparée pour gérer l’AJAX
    private function handleAjaxForm($form, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('app_categorie'),
                'message' => 'Catégorie créée avec succès !'
            ]);
        }

        // Collecte des erreurs par champ
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()->getName();
            $errors[$field][] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'errors' => $errors
        ]);
    }
}
