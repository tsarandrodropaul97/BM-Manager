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
use App\Service\NotificationService;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

#[Route('/avances')]
class AvanceSurLoyerController extends AbstractController
{
    #[Route('', name: 'app_avance_index', methods: ['GET', 'POST'])]
    public function index(
        AvanceSurLoyerRepository $avanceRepository,
        \App\Repository\BiensRepository $biensRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $search = $request->query->get('search');
        $bienId = $request->query->get('bienId');
        $dateStr = $request->query->get('date');
        $status = $request->query->get('status');
        $order = $request->query->get('order', 'DESC');

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
        } else {
            if ($search) {
                $qb->andWhere('l.nom LIKE :search OR l.prenom LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
            if ($bienId) {
                $qb->andWhere('b.id = :bienId')->setParameter('bienId', $bienId);
            }
        }

        if ($dateStr) {
            $qb->andWhere('a.dateAccord = :date')->setParameter('date', $dateStr);
        }

        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        // On garde une copie du QB pour la pagination avant d'exécuter l'autre
        $paginationQb = clone $qb;

        $query = $qb->orderBy('a.dateAccord', $order)->getQuery();

        // On récupère tout pour les stats avant de paginer
        $allResults = $query->getResult();

        // Calcul des occurrences de dates pour le surlignage
        $dateCounts = [];
        foreach ($allResults as $a) {
            $d = $a->getDateAccord()->format('Y-m-d');
            $dateCounts[$d] = ($dateCounts[$d] ?? 0) + 1;
        }

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
        foreach ($allResults as $a) {
            $uniqueLocataires[$a->getLocataire()->getId()] = $a->getLocataire();
        }

        if (count($uniqueLocataires) === 1) {
            $locataire = reset($uniqueLocataires);
            $stats['locataireStats'] = $locataire;
            // Ne compter que les avances validées dans le total historique pour les stats
            $totalHistorique = 0;
            foreach ($avanceRepository->findBy(['locataire' => $locataire, 'status' => 'validée']) as $v) {
                $totalHistorique += (float)$v->getMontantTotal();
            }

            // Date de début : Soit définie manuellement, soit la plus ancienne avance
            $dateDebut = $locataire->getDateDebutDeduction();
            if (!$dateDebut) {
                foreach ($allResults as $a) {
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
                    $aujourdhui = new \DateTime('first day of this month');
                    $dateRef = \DateTime::createFromInterface($dateDebut);
                    $dateRef->modify('first day of this month');

                    $moisConsommes = 0;
                    if ($aujourdhui > $dateRef) {
                        $diff = $dateRef->diff($aujourdhui);
                        $moisConsommes = ($diff->y * 12) + $diff->m;
                    }

                    $creditRestant = max(0, $totalHistorique - ($moisConsommes * $loyer));
                    $nbMoisCouverts = floor($creditRestant / $loyer);

                    $dateDepartCouverture = ($dateRef > $aujourdhui) ? clone $dateRef : clone $aujourdhui;
                    /** @var \DateTime $dateReprise */
                    $dateReprise = clone $dateDepartCouverture;
                    if ($nbMoisCouverts > 0) {
                        $dateReprise->modify('+' . (int)$nbMoisCouverts . ' months');
                    }

                    $stats['totalAvance'] = $creditRestant;
                    $stats['nbMoisCouverts'] = $nbMoisCouverts;
                    $stats['dateReprise'] = $dateReprise;
                    $stats['reliquatGlobal'] = $creditRestant % $loyer;
                }
            } else {
                $stats['totalAvance'] = $totalHistorique;
            }
        } else {
            // Stats globales simples pour Admin sans locataire précis
            $totalGlobal = 0;
            foreach ($allResults as $a) {
                if ($a->getStatus() === 'validée') {
                    $totalGlobal += (float)$a->getMontantTotal();
                }
            }
            $stats['totalAvance'] = $totalGlobal;
        }

        // Pagination
        $pagination = $paginator->paginate(
            $paginationQb->orderBy('a.dateAccord', $order),
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        // Préparation du formulaire pour la modal
        $avance = new AvanceSurLoyer();
        if ($isAdmin && isset($stats['locataireStats']) && $stats['locataireStats']) {
            $avance->setLocataire($stats['locataireStats']);
        } elseif (!$isAdmin && $user->getLocataire()) {
            $avance->setLocataire($user->getLocataire());
        }

        $form = $this->createForm(AvanceSurLoyerType::class, $avance, [
            'action' => $this->generateUrl('app_avance_new'),
            'method' => 'POST',
            'is_admin' => $isAdmin
        ]);

        $oldestDate = null;
        if (!empty($allResults)) {
            $oldestDate = end($allResults)->getDateAccord();
        }

        return $this->render('avance/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form->createView(),
            'stats' => $stats,
            'oldestDate' => $oldestDate,
            'dateCounts' => $dateCounts,
            'biens' => $biensRepository->findAll(),
            'filters' => [
                'search' => $search,
                'bienId' => $bienId,
                'date' => $dateStr,
                'status' => $status,
                'order' => $order
            ]
        ]);
    }

    #[Route('/new', name: 'app_avance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, NotificationService $notificationService): Response
    {
        $avance = new AvanceSurLoyer();
        /** @var User $user */
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
            $detailsJson = $form->get('montantDetails')->getData();
            if ($detailsJson) {
                $avance->setMontantDetails(json_decode($detailsJson, true));
            }

            $avance->setCreatedBy($user);
            if ($isAdmin) {
                $avance->setIsApprovedByAdmin(true);
            } else {
                $avance->setIsApprovedByLocataire(true);
            }

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

            // Notifications par email
            // if ($isAdmin) {
            //     // Créé par admin -> Notifier le locataire
            //     $notificationService->notifyTenantOnNewAvance($avance);
            // } else {
            //     // Créé par locataire -> Notifier le propriétaire
            //     $notificationService->notifyOwnerOnNewAvance($avance);
            // }

            return $this->redirectToRoute('app_avance_index');
        }

        return $this->render('avance/new.html.twig', [
            'avance' => $avance,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_avance_edit', methods: ['POST'])]
    public function edit(Request $request, AvanceSurLoyer $avance, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        /** @var User $user */
        $user = $this->getUser();

        // Sécurité
        if (!$isAdmin) {
            $locataire = $user->getLocataire();
            if (!$locataire || $avance->getLocataire()->getId() !== $locataire->getId()) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
            // Un locataire ne peut pas modifier une avance déjà validée
            if ($avance->getStatus() === 'validée') {
                $this->addFlash('warning', 'Vous ne pouvez pas modifier une avance déjà validée.');
                return $this->redirectToRoute('app_avance_index');
            }
        }

        $form = $this->createForm(AvanceSurLoyerType::class, $avance, [
            'is_admin' => $isAdmin
        ]);

        // Supprimer la contrainte required pour l'édition de document
        $form->add('document', FileType::class, [
            'label' => 'Preuve de la demande (Papier signé, reçu, etc.)',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '20M',
                    'mimeTypes' => [
                        'application/pdf',
                        'application/x-pdf',
                        'image/jpeg',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Veuillez uploader un document PDF ou une image valide.',
                ])
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $detailsJson = $form->get('montantDetails')->getData();
            if ($detailsJson) {
                $avance->setMontantDetails(json_decode($detailsJson, true));
            }

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
                    return $this->redirectToRoute('app_avance_index');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'L\'avance a été modifiée avec succès.');

            return $this->redirectToRoute('app_avance_index');
        }

        return $this->redirectToRoute('app_avance_index');
    }

    #[Route('/{id}', name: 'app_avance_show', methods: ['GET', 'POST'])]
    public function show(AvanceSurLoyer $avance, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $locataire = $user->getLocataire();
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

    #[Route('/{id}/approve', name: 'app_avance_approve', methods: ['POST'])]
    public function approve(AvanceSurLoyer $avance, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $locataire = $user->getLocataire();

        if ($isAdmin) {
            $avance->setIsApprovedByAdmin(true);
        } elseif ($locataire && $avance->getLocataire()->getId() === $locataire->getId()) {
            $avance->setIsApprovedByLocataire(true);
        } else {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($avance->isApprovedByAdmin() && $avance->isApprovedByLocataire()) {
            $avance->setStatus('validée');
            $entityManager->flush(); // S'assurer que le statut est en base avant d'envoyer le reçu

            // Envoyer le reçu au locataire
            $notificationService->sendReceiptToTenant($avance);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Validation enregistrée.');

        return $this->redirectToRoute('app_avance_index');
    }

    #[Route('/{id}/delete', name: 'app_avance_delete', methods: ['POST'])]
    public function delete(Request $request, AvanceSurLoyer $avance, EntityManagerInterface $entityManager): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        /** @var User $user */
        $user = $this->getUser();

        if (!$isAdmin) {
            $locataire = $user->getLocataire();
            if (!$locataire || $avance->getLocataire()->getId() !== $locataire->getId()) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
            // Optionnel : empêcher la suppression d'une avance déjà validée par l'admin pour le locataire
            // if ($avance->getStatus() === 'validée') {
            //     $this->addFlash('error', 'Vous ne pouvez pas supprimer une avance déjà validée.');
            //     return $this->redirectToRoute('app_avance_index');
            // }
        }

        if ($this->isCsrfTokenValid('delete' . $avance->getId(), $request->getPayload()->getString('_token'))) {
            // Supprimer les fichiers physiques des justificatifs liés
            foreach ($avance->getDocuments() as $doc) {
                $filePath = $this->getParameter('avances_directory') . '/' . $doc->getFilename();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $entityManager->remove($avance);
            $entityManager->flush();
            $this->addFlash('success', 'L\'avance a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_avance_index');
    }
}
