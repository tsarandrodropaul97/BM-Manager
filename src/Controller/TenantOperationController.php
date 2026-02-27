<?php

namespace App\Controller;

use App\Repository\OperationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-evenements')]
#[IsGranted('ROLE_USER')]
class TenantOperationController extends AbstractController
{
    #[Route('/', name: 'app_tenant_operation_index', methods: ['GET'])]
    public function index(OperationRepository $operationRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $locataire = $user->getLocataire();

        if (!$locataire) {
            return $this->redirectToRoute('app_dashboard');
        }

        $operations = $operationRepository->findForLocataire($locataire);

        return $this->render('tenant_operation/index.html.twig', [
            'operations' => $operations,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_operation_show', methods: ['GET'])]
    public function show(\App\Entity\Operation $operation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $locataire = $user->getLocataire();

        // Check if global or if the tenant is targeted
        if (!$operation->isGlobal() && !$operation->getTargetLocataires()->contains($locataire)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        return $this->render('tenant_operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }
}
