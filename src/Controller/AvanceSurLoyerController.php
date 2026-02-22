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
    #[Route('', name: 'app_avance_index', methods: ['GET'])]
    public function index(AvanceSurLoyerRepository $avanceRepository): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($isAdmin) {
            $avances = $avanceRepository->findAll();
        } else {
            $locataire = $user->getLocataire();
            $avances = $locataire ? $avanceRepository->findBy(['locataire' => $locataire]) : [];
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
        ]);
    }

    #[Route('/new', name: 'app_avance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $avance = new AvanceSurLoyer();
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Initialisation du locataire si c'est un locataire qui crée
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
            // Sauvegarde de l'avance d'abord
            $entityManager->persist($avance);

            // Gestion du document initial (obligatoire)
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
            $this->addFlash('success', 'L\'avance sur loyer a été enregistrée. Elle sera déduite de vos futurs loyers.');

            return $this->redirectToRoute('app_avance_show', ['id' => $avance->getId()]);
        }

        return $this->render('avance/new.html.twig', [
            'avance' => $avance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_avance_show', methods: ['GET', 'POST'])]
    public function show(AvanceSurLoyer $avance, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Sécurité : Un locataire ne peut voir que ses avances
        if (!$this->isGranted('ROLE_ADMIN')) {
            $locataire = $this->getUser()->getLocataire();
            if (!$locataire || $avance->getLocataire()->getId() !== $locataire->getId()) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette avance.');
            }
        }

        // Gestion de l'upload de document supplémentaire (partagé)
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

                    $this->addFlash('success', 'Document ajouté avec succès.');
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload du document.');
                }
            }
            return $this->redirectToRoute('app_avance_show', ['id' => $avance->getId()]);
        }

        // Calculs des statistiques
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

        $montantDeduitCeMois = min($loyerInitial, $montantAvance);
        $resteAPayerCeMois = max(0, $loyerInitial - $montantAvance);
        $soldeAvanceRestant = max(0, $montantAvance - $loyerInitial);

        return $this->render('avance/show.html.twig', [
            'avance' => $avance,
            'stats' => [
                'loyerInitial' => $loyerInitial,
                'montantDeduit' => $montantDeduitCeMois,
                'resteAPayer' => $resteAPayerCeMois,
                'soldeRestant' => $soldeAvanceRestant,
            ],
            'contrat' => $contrat
        ]);
    }
}
