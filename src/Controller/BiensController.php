<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BiensController extends AbstractController
{
    #[Route('/liste/biens', name: 'app_biens')]
    public function index(): Response
    {
        return $this->render('biens/index.html.twig', [
            'controller_name' => 'BiensController',
        ]);
    }

    #[Route('/ajouter/biens', name: 'app_bien_ajouter')]
    public function ajout(): Response
    {
        return $this->render('biens/ajout.html.twig', [
            'controller_name' => 'BiensController',
        ]);
    }
}
