<?php

/**
 * NurseController – RMDB Hospital API
 *
 * We expose REST endpoints for nurse management and authentication.
 * All routes are prefixed with /nurse. We define /create, /index, /name/{name},
 * /login before /{id} so that "index" and "login" are not matched as numeric ids.
 */

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
    }

    /** We create a new nurse in the database from JSON (user, pw required; name, title, specialty, etc. optional). */
    #[Route('/create', name: 'nurse_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user']) || !isset($data['pw'])) {
            return $this->json(['error' => 'Missing required fields: user and pw.'], Response::HTTP_BAD_REQUEST);
        }

        $existingNurse = $this->nurseRepository->findOneBy(['user' => $data['user']]);
        if ($existingNurse) {
            return $this->json(['error' => 'A nurse with this username already exists.'], Response::HTTP_CONFLICT);
        }

        $nurse = new Nurse();
        $nurse->setUser($data['user']);
        $nurse->setPw($data['pw']);
        $nurse->setName($data['name'] ?? $data['user']);
        $nurse->setTitle($data['title'] ?? null);
        $nurse->setSpecialty($data['specialty'] ?? null);
        $nurse->setDescription($data['description'] ?? null);
        $nurse->setLocation($data['location'] ?? null);
        $nurse->setAvailability($data['availability'] ?? null);
        $nurse->setImage($data['image'] ?? null);

        try {
            $this->entityManager->persist($nurse);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to save nurse: ' . $e->getMessage());
            return $this->json(['error' => 'Failed to save nurse.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_CREATED);
    }

    /** We retrieve all nurses, or we filter by query params: name, specialty, location, availability. */
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error('Error fetching nurses: ' . $e->getMessage());
            return $this->json([
                'error' => 'Error fetching nurses from database',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** We find a nurse by username (user field) and return her/his public data as JSON. */
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $nurse = $this->nurseRepository->findOneBy(['user' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_OK);
    }

    /** We update a nurse by id from JSON (name, title, specialty, description, location, availability, image); we never update user or pw. */
    #[Route('/update/{id}', name: 'nurse_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $nurse = $this->nurseRepository->find($id);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON content.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $nurse->setName($data['name']);
        }
        if (array_key_exists('title', $data)) {
            $nurse->setTitle($data['title']);
        }
        if (array_key_exists('specialty', $data)) {
            $nurse->setSpecialty($data['specialty']);
        }
        if (array_key_exists('description', $data)) {
            $nurse->setDescription($data['description']);
        }
        if (array_key_exists('location', $data)) {
            $nurse->setLocation($data['location']);
        }
        if (array_key_exists('availability', $data)) {
            $nurse->setAvailability($data['availability']);
        }
        if (array_key_exists('image', $data)) {
            $nurse->setImage($data['image']);
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to update nurse: ' . $e->getMessage());
            return $this->json(['error' => 'Failed to update nurse.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_OK);
    }

    /** We delete a nurse by id; we return 204 on success. */
    #[Route('/delete/{id}', name: 'nurse_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $nurse = $this->nurseRepository->find($id);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($nurse);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete nurse: ' . $e->getMessage());
            return $this->json(['error' => 'Failed to delete nurse.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /** We handle login: we expect JSON with user and pw; we return success and nurse (without pw) on match. */
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
            $this->logger->info('User logged in.', ['username' => $user]);
            return $this->json([
                'success' => true,
                'message' => 'Login successful.',
                'nurse' => $this->mapNurseToArray($nurse)
            ], Response::HTTP_OK);
        }

        return $this->json(['success' => false, 'message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }

    /** We find a nurse by numeric id and return her/his public data as JSON. */
    #[Route('/{id}', name: 'nurse_find_by_id', methods: ['GET'])]
    public function findById(int $id): JsonResponse
    {
        $nurse = $this->nurseRepository->find($id);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->mapNurseToArray($nurse), Response::HTTP_OK);
    }

    /** We map a Nurse entity to an array for API responses; we never include the password. */
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
