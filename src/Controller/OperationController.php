<?php

namespace App\Controller;

use App\Entity\Operation;
use App\Entity\OperationFile;
use App\Form\OperationType;
use App\Repository\OperationRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/admin/operation')]
#[IsGranted('ROLE_ADMIN')]
class OperationController extends AbstractController
{
    #[Route('/', name: 'app_operation_index', methods: ['GET'])]
    public function index(Request $request, OperationRepository $operationRepository): Response
    {
        $filters = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'date' => $request->query->get('date'),
        ];

        return $this->render('operation/index.html.twig', [
            'operations' => $operationRepository->findByFilters($filters),
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_operation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('files')->getData();
            if ($files) {
                foreach ($files as $file) {
                    $fileName = $fileUploader->upload($file);

                    $operationFile = new OperationFile();
                    $operationFile->setFilePath($fileName);
                    $operationFile->setOriginalName($file->getClientOriginalName());
                    $operation->addFile($operationFile);
                }
            }

            $entityManager->persist($operation);
            $entityManager->flush();

            $this->addFlash('success', 'L\'opération a été créée avec succès.');

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/new.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_operation_show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_operation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('files')->getData();
            if ($files) {
                foreach ($files as $file) {
                    $fileName = $fileUploader->upload($file);

                    $operationFile = new OperationFile();
                    $operationFile->setFilePath($fileName);
                    $operationFile->setOriginalName($file->getClientOriginalName());
                    $operation->addFile($operationFile);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'opération a été mise à jour.');

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_operation_delete', methods: ['POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $operation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
            $this->addFlash('success', 'L\'opération a été supprimée.');
        }

        return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}
