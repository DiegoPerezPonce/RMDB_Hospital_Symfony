<?php
 
namespace App\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;
 
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
 
    // We find a nurse by their ID.
    // We return nurse data if found, or an error message.
    #[Route('/id/{id}', name: 'nurse_find_by_id', methods: ['GET'])]
    public function findById(int $id): JsonResponse
    {
        $nurses = $this->loadNurses();
 
        foreach ($nurses as $nurse) {
            if (isset($nurse['id']) && (int)$nurse['id'] === $id) {
                return $this->json($nurse, Response::HTTP_OK);
            }
        }
 
        $this->logger->warning('We could not find a nurse with the given ID.', ['id' => $id]);
        return $this->json(
            ['error' => 'We could not find the nurse with the given ID.'],
            Response::HTTP_NOT_FOUND
        );
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
 
        $filesystem = new Filesystem();
        // We check if the nurses file exists.
        if (!$filesystem->exists(self::NURSES_FILE)) {
            $this->logger->error('We could not find the nurses file at ' . self::NURSES_FILE);
            return $this->json(
                ['success' => false, 'message' => 'Internal server error: we could not find the nurses file.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
 
        // We try to read the nurses file content.
        $nursesContent = file_get_contents(self::NURSES_FILE);
 
        if ($nursesContent === false) {
            $this->logger->error('We could not read the nurses.json file at ' . self::NURSES_FILE);
            return $this->json(
                ['success' => false, 'message' => 'Internal server error: we could not read the nurses file.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
 
        // We decode the nurses file content to an array.
        $nurses = json_decode($nursesContent, true);
 
        // We check for any JSON decoding errors.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('We failed to decode nurses.json: ' . json_last_error_msg(), ['file_content' => $nursesContent]);
            return $this->json(
                ['success' => false, 'message' => 'Internal server error: we encountered an error decoding the nurses file.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
 
        // If the result is not an array, we initialize it as empty.
        if (!is_array($nurses)) {
            $nurses = [];
        }
 
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