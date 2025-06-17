<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Secret implements JsonSerializable
{
    private string $hash;
    private string $secret;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $expiresAt = null;
    private int $remainingViews;

    public function __construct()
    {
        $this->hash = bin2hex(random_bytes(16));
        $this->createdAt = new DateTimeImmutable();
        $this->remainingViews = 1;
    }

    #[Serializer\SerializedName('secretText')]
    public function getSecretText(): string
    {
        return $this->secret;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRemainingViews(): int
    {
        return $this->remainingViews;
    }

    public function setSecret(string $secret): self
    {
        if (empty(trim($secret))) {
            throw new InvalidArgumentException('Secret text cannot be empty');
        }
        $this->secret = trim($secret);
        return $this;
    }

    public function setExpireAfterViews(int $views): self
    {
        if ($views < 1) {
            throw new InvalidArgumentException('View count must be greater than 0');
        }
        $this->remainingViews = $views;
        return $this;
    }

    public function setExpireAfter(int $minutes): self
    {
        if ($minutes > 0) {
            $this->expiresAt = (new DateTimeImmutable())->modify("+$minutes minutes");
        }
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->remainingViews <= 0) {
            return true;
        }

        if ($this->expiresAt && $this->expiresAt < new DateTimeImmutable()) {
            return true;
        }

        return false;
    }

    public function decrementRemainingViews(): void
    {
        $this->remainingViews--;
    }

    public function jsonSerialize(): array
    {
        return [
            'secretText' => $this->getSecretText(),
            'hash' => $this->hash,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'expiresAt' => $this->expiresAt?->format(DATE_ATOM),
            'remainingViews' => $this->remainingViews,
            'expired' => $this->isExpired()
        ];
    }
}