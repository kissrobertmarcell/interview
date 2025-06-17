<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Secret;
use App\Service\SecretManager;
use PHPUnit\Framework\TestCase;

final class SecretManagerTest extends TestCase
{
    private string $testDir;
    private SecretManager $manager;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'secret_test_' . uniqid();
        mkdir($this->testDir);
        $this->manager = new SecretManager($this->testDir);
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

    public function testSaveAndFind(): void
    {
        $secret = new Secret();
        $secret->setSecret('test message');
        
        $this->manager->save($secret);
        $found = $this->manager->find($secret->getHash());
        
        $this->assertNotNull($found);
        $this->assertEquals('test message', $found->getSecretText());
    }
}