<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface; // We import LoggerInterface

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private const NURSES_FILE = __DIR__ . '/../../public/nurses.json'; // We define the nurses file path

    private LoggerInterface $logger; // We declare our logger property

    // We inject the logger service into our constructor
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // We load the nurses data from the JSON file.
    // We return an array of nurse data, or an empty array if invalid.
    private function loadNurses(): array
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/nurses.json';

        if (!file_exists($filePath)) {
            $this->logger->warning('We could not find the nurses file: ' . $filePath);
            return [];
        }

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $this->logger->error('We failed to read the nurses file: ' . $filePath);
            return [];
        }

        $data = json_decode($fileContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('We failed to decode nurses JSON from ' . $filePath . ': ' . json_last_error_msg());
            return [];
        }

        return $data ?? [];
    }
    
    // We save the nurses array to the JSON file.
    // Returns true on success, false on failure.
    private function saveNurses(array $nurses): bool
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/nurses.json';
        
        $jsonData = json_encode($nurses, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filePath, $jsonData) === false) {
            $this->logger->error('We failed to write to the nurses file: ' . $filePath);
            return false;
        }
        
        return true;
    }

    // We create a new nurse.
    // We return the new nurse's data on success, or an error.
    #[Route('/create', name: 'nurse_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // We validate that 'user' and 'pw' are provided.
        if (!isset($data['user']) || !isset($data['pw'])) {
            return $this->json(['error' => 'We are missing required fields: user and pw.'], Response::HTTP_BAD_REQUEST);
        }

        $nurses = $this->loadNurses();

        // We check if a nurse with this username already exists.
        foreach ($nurses as $nurse) {
            if (isset($nurse['user']) && strcasecmp($nurse['user'], $data['user']) === 0) {
                return $this->json(['error' => 'A nurse with this username already exists.'], Response::HTTP_CONFLICT);
            }
        }
        
        // We create the new nurse. Any additional data from the request is included.
        $newNurse = $data;

        // We add the new nurse to our list.
        $nurses[] = $newNurse;

        // We save the updated list of nurses.
        if (!$this->saveNurses($nurses)) {
            return $this->json(['error' => 'We failed to save the new nurse.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // For security, we do not return the password in the response.
        unset($newNurse['pw']);

        return $this->json($newNurse, Response::HTTP_CREATED);
    }

    // We find a nurse by their username.
    // We return nurse data if found, or an error message.
    #[Route('/name/{name}', name: 'nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $nurses = $this->loadNurses();

        foreach ($nurses as $nurse) {
            if (isset($nurse['user']) && strcasecmp($nurse['user'], $name) === 0) {
                return $this->json($nurse, Response::HTTP_OK);
            }
        }

        return $this->json(['error' => 'We could not find the nurse'], Response::HTTP_NOT_FOUND);
    }

    // We retrieve all nurses.
    // We return a list of all nurses.
    #[Route('/index', name: 'nurse_getAll', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $nurses = $this->loadNurses(); // We use our existing loadNurses method

        return new JsonResponse(data: $nurses, status: Response::HTTP_OK);
    }

    // We handle user login.
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

        $nurses = $this->loadNurses();

        // We iterate through each nurse to find a match.
        foreach ($nurses as $nurse) {
            // We check if user and password match.
            if (isset($nurse['user']) && isset($nurse['pw']) &&
                $nurse['user'] === $user && $nurse['pw'] === $pw) {
                // Login successful.
                $this->logger->info('We successfully logged in the user.', ['username' => $user]);
                return $this->json(
                    ['success' => true, 'message' => 'Login successful.'],
                    Response::HTTP_OK
                );
            }
        }

        // We found no nurse with the given credentials.
        $this->logger->warning('We detected an invalid login attempt.', ['username' => $user]);
        return $this->json(
            ['success' => false, 'message' => 'Invalid credentials.'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}