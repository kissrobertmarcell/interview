<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Secret;
use App\Service\SecretStorage;
use PHPUnit\Framework\TestCase;

final class SecretStorageTest extends TestCase
{
    private string $testDir;
    private SecretStorage $storage;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/secret-test-' . uniqid();
        mkdir($this->testDir);
        $this->storage = new SecretStorage($this->testDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->testDir . '/*'));
        rmdir($this->testDir);
    }

    public function testSaveAndFind(): void
    {
        $secret = new Secret();
        $secret->setSecret('test storage');
        $secret->setExpireAfterViews(2);  // Now using the new method

        $this->storage->save($secret);

        $found = $this->storage->find($secret->getHash());
        $this->assertNotNull($found);
        $this->assertEquals($secret->getHash(), $found->getHash());
        $this->assertEquals('test storage', $found->getSecret());
        $this->assertEquals(2, $found->getRemainingViews());
    }

    public function testFindNonExistent(): void
    {
        $this->assertNull($this->storage->find('non-existent-hash'));
    }

    public function testOverwrite(): void
    {
        $secret = new Secret();
        $secret->setSecret('original');

        $this->storage->save($secret);
        $hash = $secret->getHash();

        $secret->setSecret('updated');
        $this->storage->save($secret);

        $found = $this->storage->find($hash);
        $this->assertEquals('updated', $found->getSecret());
    }
}