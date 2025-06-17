<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use JsonSerializable;

class Secret implements JsonSerializable
{
    private string $hash;
    private string $secretText;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $expiresAt = null;
    private int $remainingViews;

    public function __construct()
    {
        $this->hash = bin2hex(random_bytes(16));
        $this->createdAt = new DateTimeImmutable();
        $this->remainingViews = 1;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getSecret(): string
    {
        return $this->secretText;
    }

    public function setSecret(string $secret): self
    {
        $this->secretText = $secret;
        return $this;
    }

    public function getRemainingViews(): int
    {
        return $this->remainingViews;
    }

    public function decrementRemainingViews(): void
    {
        $this->remainingViews--;
    }

    public function setExpireAfter(int $minutes): self
    {
        if ($minutes > 0) {
            $this->expiresAt = (new DateTimeImmutable())->modify("+{$minutes} minutes");
        }
        return $this;
    }

    public function setExpireAfterViews(int $views): self
    {
        $this->remainingViews = $views;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        if ($this->remainingViews < 1) {
            return true;
        }

        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'hash' => $this->hash,
            'secretText' => $this->secretText,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'expiresAt' => $this->expiresAt?->format(DATE_ATOM),
            'remainingViews' => $this->remainingViews
        ];
    }
}