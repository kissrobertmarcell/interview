<?php
namespace App\Tests\Entity;

use App\Entity\Secret;
use PHPUnit\Framework\TestCase;

class SecretTest extends TestCase
{
    private Secret $secret;

    protected function setUp(): void
    {
        $this->secret = new Secret();
    }

    public function testNewSecretHasHash(): void
    {
        $this->assertNotEmpty($this->secret->getHash());
        $this->assertEquals(32, strlen($this->secret->getHash()));
    }

    public function testNewSecretHasCreatedAtTimestamp(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->secret->getCreatedAt());
    }

    public function testExpiryAfterViews(): void
    {
        $this->secret->setExpireAfterViews(2);
        $this->assertEquals(2, $this->secret->getRemainingViews());
        
        $this->secret->decrementRemainingViews();
        $this->assertEquals(1, $this->secret->getRemainingViews());
        $this->assertFalse($this->secret->isExpired());
        
        $this->secret->decrementRemainingViews();
        $this->assertEquals(0, $this->secret->getRemainingViews());
        $this->assertTrue($this->secret->isExpired());
    }

    public function testExpiryAfterTime(): void
    {
        $this->secret->setExpireAfter(0);
        $this->assertNull($this->secret->getExpiresAt());

        $this->secret->setExpireAfter(5);
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->secret->getExpiresAt());
        $this->assertFalse($this->secret->isExpired());

        // Create a secret that's already expired
        $secret = new Secret();
        $secret->setExpireAfter(1);
        // Simulate time passing
        $expiresAt = new \ReflectionProperty(Secret::class, 'expiresAt');
        $expiresAt->setAccessible(true);
        $expiresAt->setValue($secret, (new \DateTimeImmutable())->modify('-1 minute'));
        
        $this->assertTrue($secret->isExpired());
    }

    public function testInvalidViewsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->secret->setExpireAfterViews(0);
    }

    public function testSecretTextSerialization(): void
    {
        $text = "My secret message";
        $this->secret->setSecret($text);
        $this->assertEquals($text, $this->secret->getSecretText());
    }

    public function testJsonSerialization(): void
    {
        $secret = new Secret();
        $secret->setSecret('serialization test')
            ->setExpireAfterViews(2)
            ->setExpireAfter(5);

        $json = json_encode($secret);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('secretText', $data);
        $this->assertArrayHasKey('hash', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('expiresAt', $data);
        $this->assertArrayHasKey('remainingViews', $data);
        $this->assertArrayHasKey('expired', $data);
    }

    public function testEmptySecretThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $secret = new Secret();
        $secret->setSecret('   ');
    }
}