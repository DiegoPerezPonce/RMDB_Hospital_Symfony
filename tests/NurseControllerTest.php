<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NurseControllerTest extends WebTestCase
{
    private $client = null;
    private ?ContainerInterface $container = null;
    private Filesystem $filesystem;
    private string $nursesTestFilePath;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->nursesTestFilePath = $this->container->getParameter('app.nurses_file_path');
        $this->filesystem = new Filesystem();
        $this->createTestNursesFile();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->nursesTestFilePath)) {
            $this->filesystem->remove($this->nursesTestFilePath);
        }
        parent::tearDown();
        $this->client = null;
        $this->container = null;
    }

    private function createTestNursesFile(): void
    {
        $testNursesData = [
            ['id' => 1, 'user' => 'john.doe', 'pw' => 'password123', 'name' => 'John Doe', 'specialty' => 'Pediatrics'],
            ['id' => 2, 'user' => 'jane.smith', 'pw' => 'securepass', 'name' => 'Jane Smith', 'specialty' => 'Cardiology'],
        ];
        $this->filesystem->dumpFile($this->nursesTestFilePath, json_encode($testNursesData, JSON_PRETTY_PRINT));
    }

    public function testGetAllNurses(): void
    {
        $this->client->request('GET', '/nurse/index');
        $this->assertResponseIsSuccessful();
        
        $expectedData = [
            ['id' => 1, 'user' => 'john.doe', 'name' => 'John Doe', 'specialty' => 'Pediatrics'],
            ['id' => 2, 'user' => 'jane.smith', 'name' => 'Jane Smith', 'specialty' => 'Cardiology'],
        ];
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedData, $responseData);
    }

    public function testFindNurseByNameSuccess(): void
    {
        $this->client->request('GET', '/nurse/name/john.doe');
        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('pw', $responseContent);
        $this->assertEquals('john.doe', $responseContent['user']);
    }

    public function testFindNurseByNameNotFound(): void
    {
        $this->client->request('GET', '/nurse/name/nonexistent');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'We could not find the nurse'], $responseData);
    }

    public function testFindNurseByIdSuccess(): void
    {
        $this->client->request('GET', '/nurse/id/1');
        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('pw', $responseContent);
        $this->assertEquals(1, $responseContent['id']);
    }

    public function testFindNurseByIdNotFound(): void
    {
        $this->client->request('GET', '/nurse/id/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'We could not find the nurse with the given ID.'], $responseData);
    }

    public function testCreateNurseSuccess(): void
    {
        $this->client->request(
            'POST',
            '/nurse/create', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user' => 'new.nurse', 'pw' => 'newpass', 'name' => 'New Nurse', 'specialty' => 'ER'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('new.nurse', $responseContent['user']);
        $this->assertArrayNotHasKey('pw', $responseContent);

        $nurses = json_decode(file_get_contents($this->nursesTestFilePath), true);
        $this->assertCount(3, $nurses);
    }

    public function testCreateNurseMissingFields(): void
    {
        $this->client->request('POST', '/nurse/create', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user' => 'incomplete']));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'We are missing required fields: user and pw.'], $responseData);
    }

    public function testCreateNurseConflict(): void
    {
        $this->client->request('POST', '/nurse/create', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user' => 'john.doe', 'pw' => 'anypass']));
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'A nurse with this username already exists.'], $responseData);
    }

    public function testUpdateNurseSuccess(): void
    {
        $this->client->request('PUT', '/nurse/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Johnny Doe', 'specialty' => 'Emergency']));
        $this->assertResponseIsSuccessful();
        
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Johnny Doe', $responseContent['name']);
        $this->assertEquals('Emergency', $responseContent['specialty']);
    }
    
    public function testUpdateNurseNotFound(): void
    {
        $this->client->request('PUT', '/nurse/999', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Non Existent']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Nurse not found.'], $responseData);
    }
    
    public function testUpdateNurseUpdatePassword(): void
    {
        $this->client->request('PUT', '/nurse/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['pw' => 'new_secret_password']));
        $this->assertResponseIsSuccessful();

        $nurses = json_decode(file_get_contents($this->nursesTestFilePath), true);
        $updatedNurse = array_filter($nurses, fn($n) => $n['id'] === 1);
        $this->assertEquals('new_secret_password', reset($updatedNurse)['pw']);
    }

    public function testDeleteNurseSuccess(): void
    {
        $this->client->request('DELETE', '/nurse/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $nurses = json_decode(file_get_contents($this->nursesTestFilePath), true);
        $this->assertCount(1, $nurses);
        $this->assertEquals(2, $nurses[0]['id']); // Check that the remaining nurse is the correct one
    }

    public function testDeleteNurseNotFound(): void
    {
        $this->client->request('DELETE', '/nurse/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Nurse not found.'], $responseData);
    }

    public function testLoginSuccess(): void
    {
        $this->client->request('POST', '/nurse/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user' => 'john.doe', 'pw' => 'password123']));
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['success' => true, 'message' => 'Login successful.'], $responseData);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->client->request('POST', '/nurse/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user' => 'john.doe', 'pw' => 'wrongpass']));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['success' => false, 'message' => 'Invalid credentials.'], $responseData);
    }
}