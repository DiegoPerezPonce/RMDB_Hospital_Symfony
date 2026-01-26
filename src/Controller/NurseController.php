<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private NurseRepository $nurseRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        NurseRepository $nurseRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->nurseRepository = $nurseRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    // Create a new nurse in the database
    #[Route('/create', name: 'nurse_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user']) || !isset($data['pw'])) {
            return $this->json(['error' => 'Missing required fields: user and pw.'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingNurse = $this->nurseRepository->findOneBy(['user' => $data['user']]);
        if ($existingNurse) {
            return $this->json(['error' => 'A nurse with this username already exists.'], Response::HTTP_CONFLICT);
        }

        $nurse = new Nurse();
        $nurse->setUser($data['user']);
        $nurse->setPw($data['pw']); // In a real app, use password hashing
        $nurse->setName($data['name'] ?? $data['user']);
<<<<<<< HEAD
        $nurse->setTitle($data['title'] ?? null);
        $nurse->setSpecialty($data['specialty'] ?? null);
        $nurse->setDescription($data['description'] ?? null);
        $nurse->setLocation($data['location'] ?? null);
        $nurse->setAvailability($data['availability'] ?? null);
        $nurse->setImage($data['image'] ?? null);

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
            'name' => $nurse->getName(),
            'title' => $nurse->getTitle(),
            'specialty' => $nurse->getSpecialty(),
            'description' => $nurse->getDescription(),
            'location' => $nurse->getLocation(),
            'availability' => $nurse->getAvailability(),
            'image' => $nurse->getImage()
        ], Response::HTTP_CREATED);
    }

    // We retrieve all nurses from the database.
    // We return a list of all nurses.
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        try {
            $repository = $this->entityManager->getRepository(Nurse::class);
            $nurses = $repository->findAll();

            $data = [];
            foreach ($nurses as $nurse) {
                $data[] = [
                    'id' => $nurse->getId(),
                    'user' => $nurse->getUser(),
                    'name' => $nurse->getName(),
                    'pw' => $nurse->getPw(),
                    'title' => $nurse->getTitle(),
                    'specialty' => $nurse->getSpecialty(),
                    'description' => $nurse->getDescription(),
                    'location' => $nurse->getLocation(),
                    'availability' => $nurse->getAvailability(),
                    'image' => $nurse->getImage()
                ];
            }

            return new JsonResponse(data: $data, status: Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching nurses: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json([
                'error' => 'Error fetching nurses from database',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // We find a nurse by their username from the database.
    // We return nurse data if found, or an error message.
=======
        $nurse->setTitle($data['title'] ?? 'Nurse');
        $nurse->setSpecialty($data['specialty'] ?? 'General');
        $nurse->setDescription($data['description'] ?? '');
        $nurse->setLocation($data['location'] ?? 'Unknown');
        $nurse->setAvailability($data['availability'] ?? 'Available');
        $nurse->setImage($data['image'] ?? null);

        $this->entityManager->persist($nurse);
        $this->entityManager->flush();

        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_CREATED);
    }

    // Find a nurse by username
>>>>>>> 23b2ef7c990c66734fd43de9e7f34fdccf055445
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $nurse = $this->nurseRepository->findOneBy(['user' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

<<<<<<< HEAD
        return $this->json([
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName(),
            'pw' => $nurse->getPw(),
            'title' => $nurse->getTitle(),
            'specialty' => $nurse->getSpecialty(),
            'description' => $nurse->getDescription(),
            'location' => $nurse->getLocation(),
            'availability' => $nurse->getAvailability(),
            'image' => $nurse->getImage()
        ], Response::HTTP_OK);
    }

    // We find a nurse by their ID from the database.
    // We return nurse data if found, or an error message.
    #[Route('/{id}', name: 'nurse_find_by_id', methods: ['GET'])]
    public function findById(int $id): JsonResponse
    {
        $repository = $this->entityManager->getRepository(Nurse::class);
        $nurse = $repository->find($id);

        if (!$nurse) {
            return $this->json(['error' => 'We could not find the nurse'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName(),
            'pw' => $nurse->getPw(),
            'title' => $nurse->getTitle(),
            'specialty' => $nurse->getSpecialty(),
            'description' => $nurse->getDescription(),
            'location' => $nurse->getLocation(),
            'availability' => $nurse->getAvailability(),
            'image' => $nurse->getImage()
        ], Response::HTTP_OK);
=======
        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_OK);
    }

    // Retrieve all nurses or filter by query parameters (Search functionality)
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        $specialty = $request->query->get('specialty');
        $location = $request->query->get('location');
        $availability = $request->query->get('availability');

        if ($name || $specialty || $location || $availability) {
            $nurses = $this->nurseRepository->findFiltered($name, $specialty, $location, $availability);
        } else {
            $nurses = $this->nurseRepository->findAll();
        }

        $data = array_map([$this, 'mapNurseToArray'], $nurses);

        return $this->json($data, Response::HTTP_OK);
>>>>>>> 23b2ef7c990c66734fd43de9e7f34fdccf055445
    }

    // Handle user login
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON content.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $data['user'] ?? null;
        $pw = $data['pw'] ?? null;

        if (!$user || !$pw) {
            return $this->json(['success' => false, 'message' => 'Missing credentials.'], Response::HTTP_BAD_REQUEST);
        }

        $nurse = $this->nurseRepository->findOneBy(['user' => $user, 'pw' => $pw]);

        if ($nurse) {
            $this->logger->info('User successfully logged in.', ['username' => $user]);
            return $this->json([
                'success' => true, 
                'message' => 'Login successful.',
                'nurse' => $this->mapNurseToArray($nurse)
            ], Response::HTTP_OK);
        }

        return $this->json(['success' => false, 'message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }

    // Update an existing nurse
    #[Route('/update/{id}', name: 'nurse_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $nurse = $this->nurseRepository->find($id);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $nurse->setName($data['name']);
        if (isset($data['title'])) $nurse->setTitle($data['title']);
        if (isset($data['specialty'])) $nurse->setSpecialty($data['specialty']);
        if (isset($data['description'])) $nurse->setDescription($data['description']);
        if (isset($data['location'])) $nurse->setLocation($data['location']);
        if (isset($data['availability'])) $nurse->setAvailability($data['availability']);
        if (isset($data['image'])) $nurse->setImage($data['image']);
        if (isset($data['pw']) && !empty($data['pw'])) $nurse->setPw($data['pw']);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'nurse' => $this->mapNurseToArray($nurse)
        ], Response::HTTP_OK);
    }

    // Helper to map entity to array
    private function mapNurseToArray(Nurse $nurse): array
    {
        return [
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName(),
            'title' => $nurse->getTitle(),
            'specialty' => $nurse->getSpecialty(),
            'description' => $nurse->getDescription(),
            'location' => $nurse->getLocation(),
            'availability' => $nurse->getAvailability(),
            'image' => $nurse->getImage(),
        ];
    }
}
