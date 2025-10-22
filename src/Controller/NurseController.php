<?php

namespace App\Controller;

use App\Entity\Nurse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    // Obtener todos los nurses
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $nurses = $this->em->getRepository(Nurse::class)->findAll();

        $data = [];
        foreach ($nurses as $nurse) {
            $data[] = [
                'id' => $nurse->getId(),
                'user' => $nurse->getUser(),
                'name' => $nurse->getName(),
                'pw' => $nurse->getPw()
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    // Buscar nurse por username
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $nurse = $this->em->getRepository(Nurse::class)->findOneBy(['user' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $nurse->getId(),
            'user' => $nurse->getUser(),
            'name' => $nurse->getName(),
            'pw' => $nurse->getPw()
        ], Response::HTTP_OK);
    }

    // Login de nurse
    #[Route('/login', name: 'nurse_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $data['user'] ?? null;
        $pw = $data['pw'] ?? null;

        if (!$user || !$pw) {
            return $this->json(['success' => false, 'message' => 'Missing user or pw.'], Response::HTTP_BAD_REQUEST);
        }

        $nurse = $this->em->getRepository(Nurse::class)->findOneBy([
            'user' => $user,
            'pw' => $pw
        ]);

        if (!$nurse) {
            return $this->json(['success' => false, 'message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['success' => true, 'message' => 'Login successful.'], Response::HTTP_OK);
    }
}
