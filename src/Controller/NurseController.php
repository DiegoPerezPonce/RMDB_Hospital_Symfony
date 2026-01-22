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
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $nurse = $this->nurseRepository->findOneBy(['user' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

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
