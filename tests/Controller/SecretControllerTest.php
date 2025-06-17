<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SecretControllerTest extends WebTestCase
{
    private static $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = static::createClient();
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
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('hash', $content);
        $this->assertEquals('test secret', $content['secretText']);
        $this->assertEquals(2, $content['remainingViews']);
        $this->assertNotNull($content['createdAt']);
        $this->assertNotNull($content['expiresAt']);
    }

    public function testViewSecret(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'view test',
            'expireAfterViews' => 1,
            'expireAfter' => 5
        ]);
        
        $content = json_decode(self::$client->getResponse()->getContent(), true);
        $hash = $content['hash'];

        self::$client->request('GET', "/v1/secret/{$hash}");
        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
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

        $this->assertEquals(Response::HTTP_BAD_REQUEST, self::$client->getResponse()->getStatusCode());
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
        $this->assertStringContainsString('<remainingViews>1</remainingViews>', $response->getContent());
    }

    public function testDefaultValues(): void
    {
        self::$client->request('POST', '/v1/secret', [
            'secret' => 'default test'
        ]);

        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals(1, $content['remainingViews']);
        $this->assertNull($content['expiresAt']);
    }
}