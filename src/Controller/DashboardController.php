<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/revenuLocatif', name: 'app_revenu_locatif')]
    public function revenuLocatif(): Response
    {
        return $this->render('dashboard/revenuLocatif.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/taux', name: 'app_taux_occupation')]
    public function taux(): Response
    {
        return $this->render('dashboard/tauxOccupation.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }


    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('dashboard/maintenance.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/finance', name: 'app_finance')]
    public function finance(): Response
    {
        return $this->render('dashboard/finance.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
