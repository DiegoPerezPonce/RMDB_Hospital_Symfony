<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

#[Route('/nurse', name: 'app_nurse_')]
final class NurseController extends AbstractController
{
    private const NURSES_FILE = __DIR__ . '/../../public/nurses.json';

    #[Route('/index', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Bienvenido a nuestra API de enfermeros!',
            'path' => 'src/Controller/NurseController.php',
        ]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
     $data = json_decode($request->getContent(), true);
     
     if (json_last_error() !== JSON_ERROR_NONE) {
         return $this->json(
             ['success' => false, 'message' => 'Contenido JSON inválido.'],
             Response::HTTP_BAD_REQUEST
            );
        }
        
        $user = $data['user'] ?? null;
        $pw = $data['pw'] ?? null;
        //dd($user,$pw);
        //$user = $request -> get("user","");
        //$pw = $request -> get("pw","");
        //dd($user,$pw);

//    echo "$user $pw";

        if (!$user || !$pw) {
            return $this->json(
                ['success' => false, 'message' => 'Faltan user o pw en la solicitud.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $filesystem = new Filesystem();
        if (!$filesystem->exists(self::NURSES_FILE)) {
            error_log('Error: nurses.json file not found at ' . self::NURSES_FILE);
            return $this->json(
                ['success' => false, 'message' => 'Archivo de enfermeros no encontrado.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
                
            );
        }

        $nursesContent = file_get_contents(self::NURSES_FILE);
        if ($nursesContent === false) {
            error_log('Error: Could not read nurses.json file.');
            return $this->json(
                ['success' => false, 'message' => 'No se pudo leer el archivo de enfermeros.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $nurses = json_decode($nursesContent, true);
        if ($nurses === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error: Failed to decode nurses.json: ' . json_last_error_msg());
            return $this->json(
                ['success' => false, 'message' => 'Error al decodificar el archivo de enfermeros.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
            
        }

        if (!is_array($nurses)) {
            $nurses = [];
        }

        //dd($nurses, $user, $pw);
        foreach ($nurses as $nurse) {
            if (
                isset($nurse['user'], $nurse['pw']) &&
                $nurse['user'] === $user &&
                $nurse['pw'] === $pw
            ) {
                return $this->json(['success' => true, 'message' => 'Login exitoso.']);
            }
        }

        return $this->json(
            ['success' => false, 'message' => 'Credenciales inválidas.'],
            Response::HTTP_UNAUTHORIZED
            
        );
    }
}
