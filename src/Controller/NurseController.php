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
}