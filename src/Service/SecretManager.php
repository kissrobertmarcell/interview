<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Secret;
use InvalidArgumentException;
use RuntimeException;

final class SecretManager
{
    private string $storageFile;

    public function __construct(string $projectDir)
    {
        $this->storageFile = $projectDir . '/var/secrets.json';
        $this->initializeStorage();
    }

    public function save(Secret $secret): void
    {
        $secrets = $this->loadSecrets();
        $secrets[$secret->getHash()] = $secret;
        
        if (!$this->saveSecrets($secrets)) {
            throw new RuntimeException('Failed to save secret');
        }
    }

    public function find(string $hash): ?Secret
    {
        $secrets = $this->loadSecrets();
        return $secrets[$hash] ?? null;
    }

    private function initializeStorage(): void
    {
        if (!file_exists(dirname($this->storageFile))) {
            mkdir(dirname($this->storageFile), 0777, true);
        }
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, serialize([]));
        }
    }

    private function loadSecrets(): array
    {
        $data = file_get_contents($this->storageFile);
        return unserialize($data) ?: [];
    }

    private function saveSecrets(array $secrets): bool
    {
        return (bool) file_put_contents($this->storageFile, serialize($secrets));
    }
}