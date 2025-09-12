<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function ajout(): Response
    {
        return $this->render('categorie/ajout.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }
}
