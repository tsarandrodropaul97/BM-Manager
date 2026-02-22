<?php

namespace App\Controller;

use App\Entity\AvanceDocument;
use App\Entity\AvanceSurLoyer;
use App\Form\AvanceSurLoyerType;
use App\Repository\AvanceSurLoyerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/avances')]
class AvanceSurLoyerController extends AbstractController
{
    #[Route('', name: 'app_avance_index', methods: ['GET', 'POST'])]
    public function index(AvanceSurLoyerRepository $avanceRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $search = $request->query->get('search');
        $dateStr = $request->query->get('date');

        // Gérer la mise à jour de la date de début de déduction via POST
        if ($request->isMethod('POST') && $request->request->has('dateDebutDeduction')) {
            $locataireId = $request->request->get('locataireId');
            $newDateStr = $request->request->get('dateDebutDeduction');

            $locataireToUpdate = $entityManager->getRepository(\App\Entity\Locataire::class)->find($locataireId);
            if ($locataireToUpdate && ($isAdmin || ($user->getLocataire() && $user->getLocataire()->getId() == $locataireId))) {
                if ($newDateStr) {
                    $locataireToUpdate->setDateDebutDeduction(new \DateTime($newDateStr));
                } else {
                    $locataireToUpdate->setDateDebutDeduction(null);
                }
                $entityManager->flush();
                $this->addFlash('success', 'Date de début de déduction mise à jour.');
                return $this->redirectToRoute('app_avance_index', $request->query->all());
            }
        }

        // Construction de la requête avec filtres
        $qb = $avanceRepository->createQueryBuilder('a')
            ->leftJoin('a.locataire', 'l')
            ->leftJoin('l.bien', 'b')
            ->addSelect('l', 'b');

        if (!$isAdmin) {
            $locataire = $user->getLocataire();
            $qb->andWhere('a.locataire = :locataire')->setParameter('locataire', $locataire);
        } elseif ($search) {
            $qb->andWhere('l.nom LIKE :search OR l.prenom LIKE :search OR b.designation LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($dateStr) {
            $qb->andWhere('a.dateAccord = :date')->setParameter('date', $dateStr);
        }

        $avances = $qb->orderBy('a.dateAccord', 'DESC')->getQuery()->getResult();

        // Initialisation des stats
        $stats = [
            'totalAvance' => 0,
            'loyerMensuel' => 0,
            'nbMoisCouverts' => 0,
            'dateReprise' => null,
            'reliquatGlobal' => 0,
            'locataireStats' => null
        ];

        // Pour les stats globales ou individuelles
        $uniqueLocataires = [];
        foreach ($avances as $a) {
            $uniqueLocataires[$a->getLocataire()->getId()] = $a->getLocataire();
        }

        if (count($uniqueLocataires) === 1) {
            $locataire = reset($uniqueLocataires);
            $stats['locataireStats'] = $locataire;
            $totalHistorique = (float)$avanceRepository->getTotalAvancesByLocataire($locataire->getId());

            // Date de début : Soit définie manuellement, soit la plus ancienne avance
            $dateDebut = $locataire->getDateDebutDeduction();
            if (!$dateDebut) {
                foreach ($avances as $a) {
                    if (!$dateDebut || $a->getDateAccord() < $dateDebut) {
                        $dateDebut = $a->getDateAccord();
                    }
                }
            }

            $contrat = null;
            foreach ($locataire->getContrats() as $c) {
                if ($c->getStatut() === 'actif') {
                    $contrat = $c;
                    break;
                }
            }

            if ($contrat && $dateDebut) {
                $loyer = (float)$contrat->getLoyerHorsCharges() + (float)$contrat->getCharges();
                $stats['loyerMensuel'] = $loyer;

                if ($loyer > 0) {
                    // 1. Calculer la date de reprise finale
                    $nbMoisTotauxInitiaux = floor($totalHistorique / $loyer);
                    $dateReprise = clone $dateDebut;
                    if ($nbMoisTotauxInitiaux > 0) {
                        $dateReprise->modify('+' . (int)$nbMoisTotauxInitiaux . ' months');
                    }
                    $stats['dateReprise'] = $dateReprise;

                    // 2. Calculer combien de mois ont été consommés depuis le début
                    $aujourdhui = new \DateTime('first day of this month');
                    $dateRef = clone $dateDebut;
                    $dateRef->modify('first day of this month');

                    $moisConsommes = 0;
                    if ($aujourdhui > $dateRef) {
                        $diff = $dateRef->diff($aujourdhui);
                        $moisConsommes = ($diff->y * 12) + $diff->m;
                    }

                    // 3. Crédit Restant
                    $creditRestant = max(0, $totalHistorique - ($moisConsommes * $loyer));

                    $stats['totalAvance'] = $creditRestant;
                    $stats['nbMoisCouverts'] = floor($creditRestant / $loyer);
                    $stats['reliquatGlobal'] = $creditRestant % $loyer;
                }
            } else {
                $stats['totalAvance'] = $totalHistorique;
            }
        } else {
            // Stats globales simples pour Admin sans locataire précis
            $totalGlobal = 0;
            foreach ($avances as $a) {
                $totalGlobal += (float)$a->getMontantTotal();
            }
            $stats['totalAvance'] = $totalGlobal;
        }

        // Préparation du formulaire pour la modal
        $avance = new AvanceSurLoyer();
        if (!$isAdmin && $user->getLocataire()) {
            $avance->setLocataire($user->getLocataire());
        }

        $form = $this->createForm(AvanceSurLoyerType::class, $avance, [
            'action' => $this->generateUrl('app_avance_new'),
            'method' => 'POST',
            'is_admin' => $isAdmin
        ]);

        return $this->render('avance/index.html.twig', [
            'avances' => $avances,
            'form' => $form->createView(),
            'stats' => $stats,
            'filters' => ['search' => $search, 'date' => $dateStr]
        ]);
    }

    #[Route('/new', name: 'app_avance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $avance = new AvanceSurLoyer();
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin) {
            $locataire = $user->getLocataire();
            if (!$locataire) {
                $this->addFlash('warning', 'Vous devez être enregistré en tant que locataire pour déclarer une avance.');
                return $this->redirectToRoute('app_dashboard');
            }
            $avance->setLocataire($locataire);
        }

        $form = $this->createForm(AvanceSurLoyerType::class, $avance, [
            'is_admin' => $isAdmin
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($avance);

            $docFile = $form->get('document')->getData();
            if ($docFile) {
                $originalFilename = pathinfo($docFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $docFile->guessExtension();

                try {
                    $docFile->move($this->getParameter('avances_directory'), $newFilename);

                    $doc = new AvanceDocument();
                    $doc->setFilename($newFilename);
                    $doc->setOriginalName($docFile->getClientOriginalName());
                    $doc->setUploadedBy($user);
                    $doc->setAvance($avance);

                    $entityManager->persist($doc);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de la preuve.');
                    return $this->redirectToRoute('app_avance_new');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'L\'avance sur loyer a été enregistrée.');

            return $this->redirectToRoute('app_avance_index');
        }

        return $this->render('avance/new.html.twig', [
            'avance' => $avance,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_avance_show', methods: ['GET', 'POST'])]
    public function show(AvanceSurLoyer $avance, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $locataire = $this->getUser()->getLocataire();
            if (!$locataire || $avance->getLocataire()->getId() !== $locataire->getId()) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
        }

        if ($request->isMethod('POST') && $request->files->get('document')) {
            $file = $request->files->get('document');
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move($this->getParameter('avances_directory'), $newFilename);
                    $doc = new AvanceDocument();
                    $doc->setFilename($newFilename);
                    $doc->setOriginalName($file->getClientOriginalName());
                    $doc->setUploadedBy($this->getUser());
                    $doc->setAvance($avance);
                    $entityManager->persist($doc);
                    $entityManager->flush();
                    $this->addFlash('success', 'Document ajouté.');
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur upload.');
                }
            }
            return $this->redirectToRoute('app_avance_show', ['id' => $avance->getId()]);
        }

        $locataire = $avance->getLocataire();
        $contrat = null;
        foreach ($locataire->getContrats() as $c) {
            if ($c->getStatut() === 'actif') {
                $contrat = $c;
                break;
            }
        }

        $loyerInitial = $contrat ? (float)$contrat->getLoyerHorsCharges() + (float)$contrat->getCharges() : 0;
        $montantAvance = (float)$avance->getMontantTotal();

        return $this->render('avance/show.html.twig', [
            'avance' => $avance,
            'stats' => [
                'loyerInitial' => $loyerInitial,
                'montantDeduit' => min($loyerInitial, $montantAvance),
                'resteAPayer' => max(0, $loyerInitial - $montantAvance),
                'soldeRestant' => max(0, $montantAvance - $loyerInitial),
            ],
            'contrat' => $contrat
        ]);
    }
}
