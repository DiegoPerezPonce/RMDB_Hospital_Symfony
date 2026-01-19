<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Nurse;
use Psr\Log\LoggerInterface; // We import LoggerInterface

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    // We inject the logger service and entity manager into our constructor
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    // We create a new nurse in the database.
    // We return the new nurse's data on success, or an error.
    #[Route('/create', name: 'nurse_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // We validate that 'user' and 'pw' are provided.
        if (!isset($data['user']) || !isset($data['pw'])) {
            return $this->json(['error' => 'We are missing required fields: user and pw.'], Response::HTTP_BAD_REQUEST);
        }

        $repository = $this->entityManager->getRepository(Nurse::class);
        $existingNurse = $repository->findOneBy(['user' => $data['user']]);

        // We check if a nurse with this username already exists.
        if ($existingNurse) {
            return $this->json(['error' => 'A nurse with this username already exists.'], Response::HTTP_CONFLICT);
        }
        
        // We create the new nurse entity.
        $nurse = new Nurse();
        $nurse->setUser($data['user']);
        $nurse->setPw($data['pw']); // Storing plain text password as per original logic
        // We use user as name if name is not provided, strictly to ensure we populate the field if required.
        $nurse->setName($data['name'] ?? $data['user']);

        // We save the new nurse to the database.
        try {
            $this->entityManager->persist($nurse);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('We failed to save the new nurse: ' . $e->getMessage());
            return $this->json(['error' => 'We failed to save the new nurse.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // We return the created nurse data (excluding password for security in response).
        return $this->json([
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName()
        ], Response::HTTP_CREATED);
    }

    // We find a nurse by their username from the database.
    // We return nurse data if found, or an error message.
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $repository = $this->entityManager->getRepository(Nurse::class);
        $nurse = $repository->findOneBy(['user' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'We could not find the nurse'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName(),
            'pw' => $nurse->getPw()
        ], Response::HTTP_OK);
    }

    // We retrieve all nurses from the database.
    // We return a list of all nurses.
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $repository = $this->entityManager->getRepository(Nurse::class);
        $nurses = $repository->findAll();

        $data = [];
        foreach ($nurses as $nurse) {
            $data[] = [
                'id' => $nurse->getId(),
                'user' => $nurse->getUser(),
                'name' => $nurse->getName(),
                'pw' => $nurse->getPw()
            ];
        }

        return new JsonResponse(data: $data, status: Response::HTTP_OK);
    }

    // We handle user login using the database.
    // We return a success message on valid credentials, or an error.
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // We decode the JSON content from the request.
        $data = json_decode($request->getContent(), true);

        // We validate if the JSON content is valid.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('We received invalid JSON content during a login attempt.', ['json_error' => json_last_error_msg()]);
            return $this->json(
                ['success' => false, 'message' => 'We received invalid JSON content.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // We get 'user' and 'pw' from the data.
        $user = $data['user'] ?? null;
        $pw = $data['pw'] ?? null;

        // We verify that 'user' and 'pw' are provided.
        if (!$user || !$pw) {
            $this->logger->warning('We are missing user or password in the login request.', ['provided_data' => $data]);
            return $this->json(
                ['success' => false, 'message' => 'We are missing user or pw in the request.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $repository = $this->entityManager->getRepository(Nurse::class);
        $nurse = $repository->findOneBy(['user' => $user]);

        // We check if user exists and password matches.
        if ($nurse && $nurse->getPw() === $pw) {
            // Login successful.
            $this->logger->info('We successfully logged in the user.', ['username' => $user]);
            return $this->json(
                ['success' => true, 'message' => 'Login successful.'],
                Response::HTTP_OK
            );
        }

        // We found no nurse with the given credentials.
        $this->logger->warning('We detected an invalid login attempt.', ['username' => $user]);
        return $this->json(
            ['success' => false, 'message' => 'Invalid credentials.'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}