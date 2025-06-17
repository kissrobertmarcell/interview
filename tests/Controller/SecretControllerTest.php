<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\SecretController;
use App\Service\SecretStorage;

final class SecretControllerTest extends WebTestCase
{
    private static $client;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        self::$client = self::createClient();
        $this->testDir = self::$client->getContainer()->getParameter('app.project_dir');
        
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $secretsFile = $this->testDir . DIRECTORY_SEPARATOR . 'secrets.json';
        if (file_exists($secretsFile)) {
            unlink($secretsFile);
        }
        if (file_exists($this->testDir)) {
            rmdir($this->testDir);
        }
        parent::tearDown();
    }

    public function testCreateSecret(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'test secret',
            'expireAfterViews' => 2,
            'expireAfter' => 5
        ]);

        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('hash', $content);
        $this->assertEquals('test secret', $content['secretText']);
    }

    public function testViewSecret(): void
    {
        // Create a secret first
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'view test',
            'expireAfterViews' => 1,
            'expireAfter' => 5
        ]);
        
        $content = json_decode(self::$client->getResponse()->getContent(), true);
        $hash = $content['hash'];

        // View the secret
        self::$client->request('GET', "/v1/secret/{$hash}");
        
        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Second view should fail
        self::$client->request('GET', "/v1/secret/{$hash}");
        $this->assertEquals(Response::HTTP_NOT_FOUND, self::$client->getResponse()->getStatusCode());
    }

    public function testInvalidInput(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => '',
            'expireAfterViews' => 0,
            'expireAfter' => 0
        ]);

        $this->assertEquals(
            Response::HTTP_METHOD_NOT_ALLOWED, 
            self::$client->getResponse()->getStatusCode()
        );
    }

    public function testXmlResponse(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'xml test',
            'expireAfterViews' => 1,
            'expireAfter' => 5
        ], [], ['HTTP_ACCEPT' => 'application/xml']);

        $response = self::$client->getResponse();
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<secretText>xml test</secretText>', $response->getContent());
    }

    public function testCreateResponseWithInvalidFormat(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'test format',
            'expireAfterViews' => 1,
            'expireAfter' => 5
        ], [], ['HTTP_ACCEPT' => 'invalid/format']);

        $response = self::$client->getResponse();
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}