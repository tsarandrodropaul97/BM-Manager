<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        \App\Repository\AvanceSurLoyerRepository $avanceRepository,
        \App\Repository\BiensRepository $biensRepository,
        \App\Repository\LocataireRepository $locataireRepository,
        \App\Repository\ContratRepository $contratRepository,
        Request $request
    ): Response {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $stats = [];
        $previsions = [];
        $adminStats = [];

        $bienId = $request->query->get('bienId');

        if ($isAdmin) {
            // Stats Administrateur
            $allBiens = $biensRepository->findAll();
            $activeLocatairesCount = $locataireRepository->count(['statut' => 'actif']);
            $totalBiensCount = count($allBiens);
            $occupiedBiensCount = $biensRepository->count(['statut' => 'loue']);

            $revenuMensuel = 0;
            $contratsActifs = $contratRepository->findBy(['statut' => 'actif']);

            if ($bienId) {
                $bien = $biensRepository->find($bienId);
                if ($bien) {
                    $activeLocatairesCount = $locataireRepository->count(['bien' => $bien, 'statut' => 'actif']);
                    $contratsActifs = $contratRepository->findBy(['bien' => $bien, 'statut' => 'actif']);
                    $totalBiensCount = 1;
                    $occupiedBiensCount = $bien->getStatut() === 'loue' ? 1 : 0;
                }
            }

            foreach ($contratsActifs as $c) {
                $revenuMensuel += (float)$c->getLoyerHorsCharges() + (float)$c->getCharges();
            }

            $tauxOccupation = $totalBiensCount > 0 ? ($occupiedBiensCount / $totalBiensCount) * 100 : 0;

            // Activités récentes (10 dernières avances)
            $recentActivities = $avanceRepository->findBy([], ['dateAccord' => 'DESC'], 10);

            $adminStats = [
                'totalBiens' => $totalBiensCount,
                'activeLocataires' => $activeLocatairesCount,
                'revenuMensuel' => $revenuMensuel,
                'tauxOccupation' => round($tauxOccupation, 1),
                'recentActivities' => $recentActivities,
                'biens' => $allBiens,
                'filters' => ['bienId' => $bienId]
            ];
        }

        if (!$isAdmin && $user) {
            /** @var \App\Entity\User $user */
            $locataire = $user->getLocataire();
            if ($locataire) {
                // Trouver le contrat actif
                $contrat = null;
                foreach ($locataire->getContrats() as $c) {
                    if ($c->getStatut() === 'actif') {
                        $contrat = $c;
                        break;
                    }
                }

                if ($contrat) {
                    $loyerHC = (float)$contrat->getLoyerHorsCharges();
                    $charges = (float)$contrat->getCharges();
                    $loyerTotal = $loyerHC + $charges;

                    $totalAvances = (float)$avanceRepository->getTotalAvancesByLocataire($locataire->getId());

                    // Date de référence pour le début de déduction
                    $dateDebut = $locataire->getDateDebutDeduction();
                    if (!$dateDebut) {
                        // Chercher l'avance la plus ancienne
                        $avances = $avanceRepository->findBy(['locataire' => $locataire], ['dateAccord' => 'ASC']);
                        if (!empty($avances)) {
                            $dateDebut = $avances[0]->getDateAccord();
                        }
                    }

                    $creditRestant = $totalAvances;
                    $nbMoisCouverts = 0;
                    $dateReprise = null;

                    $aujourdhui = new \DateTime('first day of this month');
                    $dateRef = null;

                    if ($dateDebut) {
                        $dateRef = \DateTime::createFromInterface($dateDebut);
                        $dateRef->modify('first day of this month');

                        if ($loyerTotal > 0) {
                            $moisConsommes = 0;
                            if ($aujourdhui > $dateRef) {
                                $diff = $dateRef->diff($aujourdhui);
                                $moisConsommes = ($diff->y * 12) + $diff->m;
                            }

                            $creditRestant = max(0, $totalAvances - ($moisConsommes * $loyerTotal));
                        }
                    }

                    if ($loyerTotal > 0) {
                        $nbMoisCouverts = floor($creditRestant / $loyerTotal);
                        $dateDepartCouverture = ($dateRef && $dateRef > $aujourdhui) ? clone $dateRef : clone $aujourdhui;

                        /** @var \DateTime $dateReprise */
                        $dateReprise = clone $dateDepartCouverture;
                        if ($nbMoisCouverts > 0) {
                            $dateReprise->modify('+' . (int)$nbMoisCouverts . ' months');
                        }
                    }

                    $stats = [
                        'loyerHC' => $loyerHC,
                        'charges' => $charges,
                        'loyerTotal' => $loyerTotal,
                        'creditRestant' => $creditRestant,
                        'nbMoisCouverts' => $nbMoisCouverts,
                        'dateReprise' => $dateReprise,
                        'contrat' => $contrat,
                        'bien' => $contrat->getBien(),
                    ];

                    // Prévisions sur 6 mois
                    $simulCreditRestant = $creditRestant;
                    for ($i = 0; $i < 6; $i++) {
                        $moisPrevu = (new \DateTime('first day of this month'))->modify('+' . $i . ' months');

                        $deduction = 0;
                        if (!$dateRef || $moisPrevu >= $dateRef) {
                            if ($simulCreditRestant >= $loyerTotal) {
                                $deduction = $loyerTotal;
                            } else {
                                $deduction = $simulCreditRestant;
                            }
                            $simulCreditRestant = max(0, $simulCreditRestant - $deduction);
                        }

                        $aPayer = $loyerTotal - $deduction;
                        $estCouvert = ($aPayer == 0 && $deduction > 0);
                        $partiel = ($aPayer > 0 && $deduction > 0);

                        $previsions[] = [
                            'date' => $moisPrevu,
                            'loyer' => $loyerTotal,
                            'deduction' => $deduction,
                            'aPayer' => $aPayer,
                            'statut' => $estCouvert ? 'Payé (Avance)' : ($partiel ? 'Partiel' : 'À payer')
                        ];
                    }
                }
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'previsions' => $previsions,
            'adminStats' => $adminStats,
        ]);
    }

    #[Route('/revenuLocatif', name: 'app_revenu_locatif')]
    #[IsGranted('ROLE_ADMIN')]
    public function revenuLocatif(): Response
    {
        return $this->render('dashboard/revenuLocatif.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/taux', name: 'app_taux_occupation')]
    #[IsGranted('ROLE_ADMIN')]
    public function taux(): Response
    {
        return $this->render('dashboard/tauxOccupation.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }


    #[Route('/maintenance', name: 'app_maintenance')]
    #[IsGranted('ROLE_ADMIN')]
    public function maintenance(): Response
    {
        return $this->render('dashboard/maintenance.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/finance', name: 'app_finance')]
    #[IsGranted('ROLE_ADMIN')]
    public function finance(): Response
    {
        return $this->render('dashboard/finance.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
