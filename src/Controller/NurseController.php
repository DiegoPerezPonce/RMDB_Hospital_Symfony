<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get all nurses from database
     * GET /nurse/index
     */
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
    {
        try {
            $nurses = $nurseRepository->findAll();

            // Convert entities to array
            $nursesData = array_map(function (Nurse $nurse) {
                return [
                    'id' => $nurse->getId(),
                    'user' => $nurse->getUser(),
                    'name' => $nurse->getName(),
                    'pw' => $nurse->getPw()
                ];
            }, $nurses);

            return $this->json($nursesData, Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching all nurses: ' . $e->getMessage());
            return $this->json(
                ['error' => 'Failed to fetch nurses'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Find a nurse by username
     * GET /nurse/name/{name}
     */
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name, NurseRepository $nurseRepository): JsonResponse
    {
        try {
            $nurse = $nurseRepository->findOneBy(['user' => $name]);

            if (!$nurse) {
                return $this->json(
                    ['error' => 'Nurse not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json([
                'id' => $nurse->getId(),
                'user' => $nurse->getUser(),
                'name' => $nurse->getName(),
                'pw' => $nurse->getPw()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error finding nurse by name: ' . $e->getMessage());
            return $this->json(
                ['error' => 'Failed to find nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Find a nurse by ID
     * GET /nurse/id/{id}
     */
    #[Route('/id/{id}', name: 'nurse_find_by_id', methods: ['GET'])]
    public function findById(int $id, NurseRepository $nurseRepository): JsonResponse
    {
        try {
            $nurse = $nurseRepository->find($id);

            if (!$nurse) {
                $this->logger->warning('Nurse not found with ID: ' . $id);
                return $this->json(
                    ['error' => 'Nurse not found with the given ID'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json([
                'id' => $nurse->getId(),
                'user' => $nurse->getUser(),
                'name' => $nurse->getName(),
                'pw' => $nurse->getPw()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error finding nurse by ID: ' . $e->getMessage());
            return $this->json(
                ['error' => 'Failed to find nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * User login endpoint
     * POST /nurse/login
     */
    #[Route('/login', name: 'nurse_login', methods: ['POST'])]
    public function login(Request $request, NurseRepository $nurseRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Invalid JSON content in login request');
                return $this->json(
                    ['success' => false, 'message' => 'Invalid JSON content'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $data['user'] ?? null;
            $pw = $data['pw'] ?? null;

            if (!$user || !$pw) {
                $this->logger->warning('Missing user or password in login request');
                return $this->json(
                    ['success' => false, 'message' => 'Missing user or password'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $nurse = $nurseRepository->findOneBy(['user' => $user, 'pw' => $pw]);

            if ($nurse) {
                $this->logger->info('Successful login for user: ' . $user);
                return $this->json(
                    ['success' => true, 'message' => 'Login successful'],
                    Response::HTTP_OK
                );
            }

            $this->logger->warning('Invalid login attempt for user: ' . $user);
            return $this->json(
                ['success' => false, 'message' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during login: ' . $e->getMessage());
            return $this->json(
                ['success' => false, 'message' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a new nurse
     * POST /nurse/new
     */
    #[Route('/new', name: 'nurse_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['success' => false, 'message' => 'Invalid JSON content'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $data['user'] ?? null;
            $name = $data['name'] ?? null;
            $pw = $data['pw'] ?? null;

            if (!$user || !$name || !$pw) {
                return $this->json(
                    ['success' => false, 'message' => 'Missing required fields: user, name, pw'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $nurse = new Nurse();
            $nurse->setUser($user);
            $nurse->setName($name);
            $nurse->setPw($pw);

            $entityManager->persist($nurse);
            $entityManager->flush();

            $this->logger->info('Created new nurse with ID: ' . $nurse->getId());

            return $this->json([
                'success' => true,
                'message' => 'Nurse created successfully',
                'nurse' => [
                    'id' => $nurse->getId(),
                    'user' => $nurse->getUser(),
                    'name' => $nurse->getName()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error creating nurse: ' . $e->getMessage());
            return $this->json(
                ['success' => false, 'message' => 'Failed to create nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an existing nurse
     * PUT /nurse/{id}/edit
     */
    #[Route('/{id}/edit', name: 'nurse_update', methods: ['PUT'])]
    public function update(int $id, Request $request, NurseRepository $nurseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $nurse = $nurseRepository->find($id);

            if (!$nurse) {
                return $this->json(
                    ['success' => false, 'message' => 'Nurse not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['success' => false, 'message' => 'Invalid JSON content'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (isset($data['user'])) {
                $nurse->setUser($data['user']);
            }
            if (isset($data['name'])) {
                $nurse->setName($data['name']);
            }
            if (isset($data['pw'])) {
                $nurse->setPw($data['pw']);
            }

            $entityManager->flush();

            $this->logger->info('Updated nurse with ID: ' . $id);

            return $this->json([
                'success' => true,
                'message' => 'Nurse updated successfully',
                'nurse' => [
                    'id' => $nurse->getId(),
                    'user' => $nurse->getUser(),
                    'name' => $nurse->getName()
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error updating nurse: ' . $e->getMessage());
            return $this->json(
                ['success' => false, 'message' => 'Failed to update nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete a nurse
     * DELETE /nurse/{id}
     */
    #[Route('/{id}', name: 'nurse_delete', methods: ['DELETE'])]
    public function delete(int $id, NurseRepository $nurseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $nurse = $nurseRepository->find($id);

            if (!$nurse) {
                return $this->json(
                    ['success' => false, 'message' => 'Nurse not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $entityManager->remove($nurse);
            $entityManager->flush();

            $this->logger->info('Deleted nurse with ID: ' . $id);

            return $this->json([
                'success' => true,
                'message' => 'Nurse deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting nurse: ' . $e->getMessage());
            return $this->json(
                ['success' => false, 'message' => 'Failed to delete nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Show a specific nurse
     * GET /nurse/{id}/show
     */
    #[Route('/{id}/show', name: 'nurse_show', methods: ['GET'])]
    public function show(int $id, NurseRepository $nurseRepository): JsonResponse
    {
        try {
            $nurse = $nurseRepository->find($id);

            if (!$nurse) {
                return $this->json(
                    ['error' => 'Nurse not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json([
                'id' => $nurse->getId(),
                'user' => $nurse->getUser(),
                'name' => $nurse->getName(),
                'pw' => $nurse->getPw()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error showing nurse: ' . $e->getMessage());
            return $this->json(
                ['error' => 'Failed to fetch nurse'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}