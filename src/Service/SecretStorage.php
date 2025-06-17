<?php
namespace App\Service;

use App\Entity\Secret;

class SecretStorage
{
    private string $storageFile;

    public function __construct(string $projectDir)
    {
        $this->storageFile = $projectDir . '/var/secrets.json';
        if (!file_exists(dirname($this->storageFile))) {
            mkdir(dirname($this->storageFile), 0777, true);
        }
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, '[]');
        }
    }

    public function save(Secret $secret): void
    {
        $secrets = $this->loadSecrets();
        $secrets[$secret->getHash()] = $secret;
        $this->saveSecrets($secrets);
    }

    public function find(string $hash): ?Secret
    {
        $secrets = $this->loadSecrets();
        return $secrets[$hash] ?? null;
    }

    private function loadSecrets(): array
    {
        if (!file_exists($this->storageFile) || filesize($this->storageFile) === 0) {
            return [];
        }
        
        $data = file_get_contents($this->storageFile);
        $secrets = @unserialize($data);
        return $secrets !== false ? $secrets : [];
    }

    private function saveSecrets(array $secrets): void
    {
        if (!file_exists(dirname($this->storageFile))) {
            mkdir(dirname($this->storageFile), 0777, true);
        }
        file_put_contents($this->storageFile, serialize($secrets));
    }
}