<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Secret;

class SecretStorage
{
    private string $storageFile;

    public function __construct(string $projectDir)
    {
        $this->storageFile = $projectDir . '/var/storage/secrets.json';
        $this->initializeStorage();
    }

    private function initializeStorage(): void
    {
        $storageDir = dirname($this->storageFile);
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0750, true);
        }
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, serialize([]), LOCK_EX);
        }
    }

    public function save(Secret $secret): void
    {
        $secrets = $this->loadSecrets();
        $secrets[$secret->getHash()] = $secret;
        file_put_contents($this->storageFile, serialize($secrets), LOCK_EX);
    }

    public function find(string $hash): ?Secret
    {
        $secrets = $this->loadSecrets();
        return $secrets[$hash] ?? null;
    }

    private function loadSecrets(): array
    {
        $data = unserialize(file_get_contents($this->storageFile));
        return is_array($data) ? $data : [];
    }
}