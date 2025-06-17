<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Secret;
use PHPUnit\Framework\TestCase;

final class SecretTest extends TestCase
{
    private Secret $secret;

    protected function setUp(): void
    {
        $this->secret = new Secret();
        $this->secret->setSecret('test secret');
    }

    public function testSecretInitialization(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $this->secret->getHash());
        $this->assertEquals(1, $this->secret->getRemainingViews());
        $this->assertNull($this->secret->getExpiresAt());
    }

    public function testExpirationByViews(): void
    {
        $this->assertFalse($this->secret->isExpired());
        $this->secret->decrementRemainingViews();
        $this->assertTrue($this->secret->isExpired());
    }

    public function testTimeBasedExpiration(): void
    {
        $this->secret->setExpireAfter(0);
        $this->assertNull($this->secret->getExpiresAt());

        $this->secret->setExpireAfter(1);
        $this->assertNotNull($this->secret->getExpiresAt());
    }

    public function testJsonSerialization(): void
    {
        $json = json_encode($this->secret);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('hash', $data);
        $this->assertArrayHasKey('secretText', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('expiresAt', $data);
        $this->assertArrayHasKey('remainingViews', $data);
        $this->assertEquals('test secret', $data['secretText']);
        $this->assertEquals(1, $data['remainingViews']);
        $this->assertNull($data['expiresAt']);
    }
}