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
        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'secret_test_' . uniqid();
        mkdir($this->testDir);
        $this->storage = new SecretStorage($this->testDir);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDir)) {
            $this->cleanupDirectory($this->testDir);
        }
    }

    private function cleanupDirectory(string $dir): void
    {
        $files = new \DirectoryIterator($dir);
        foreach ($files as $file) {
            if ($file->isDot()) continue;
            if ($file->isDir()) {
                $this->cleanupDirectory($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public function testBasicStorage(): void
    {
        $secret = new Secret();
        $secret->setSecret('test message');
        
        $this->storage->save($secret);
        $retrieved = $this->storage->find($secret->getHash());
        
        $this->assertNotNull($retrieved);
        $this->assertEquals('test message', $retrieved->getSecretText());
    }
}