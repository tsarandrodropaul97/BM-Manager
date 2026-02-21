<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Repository\CategorieRepository;
use App\Repository\BiensRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CategorieController extends AbstractController
{
    #[Route('/categorie', name: 'app_categorie')]
    public function index(Request $request, CategorieRepository $categorieRepository, BiensRepository $biensRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $type = $request->query->get('type');
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $categorieRepository->getFilteredQueryBuilder($search, $statut, $type);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        $totalCategories = $categorieRepository->count([]);
        $actives = $categorieRepository->count(['statut' => 'active']);
        $inactives = $categorieRepository->count(['statut' => 'inactive']);

        $totalBiensAssocies = $biensRepository->createQueryBuilder('b')
            ->where('b.categorie IS NOT NULL')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('categorie/index.html.twig', [
            'pagination' => $pagination,
            'stats' => [
                'total' => $totalCategories,
                'actives' => $actives,
                'inactives' => $inactives,
                'biensAssocies' => $totalBiensAssocies
            ],
            'filters' => [
                'search' => $search,
                'statut' => $statut,
                'type' => $type,
                'limit' => $limit
            ]
        ]);
    }

    #[Route('/categorie/export', name: 'app_categorie_export')]
    public function export(Request $request, CategorieRepository $categorieRepository): StreamedResponse
    {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $type = $request->query->get('type');

        $queryBuilder = $categorieRepository->getFilteredQueryBuilder($search, $statut, $type);
        $categories = $queryBuilder->getQuery()->getResult();

        $response = new StreamedResponse(function () use ($categories) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['Nom', 'Type', 'Description', 'Nombre de biens', 'Statut', 'Date de creation']);

            foreach ($categories as $categorie) {
                fputcsv($handle, [
                    $categorie->getNom(),
                    $categorie->getType(),
                    $categorie->getDescription(),
                    $categorie->getBiens()->count(),
                    $categorie->getStatut(),
                    $categorie->getCreatedAt()->format('Y-m-d')
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="categories_export_' . date('Y-m-d') . '.csv"');

        return $response;
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
