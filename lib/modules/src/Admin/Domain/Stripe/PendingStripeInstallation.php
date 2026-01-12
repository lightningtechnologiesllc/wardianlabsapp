<?php
declare(strict_types=1);

namespace App\Admin\Domain\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;

final class PendingStripeInstallation
{
    public function __construct(
        private readonly PendingStripeInstallationId $id,
        private readonly string $linkingToken,
        private readonly string $accessToken,
        private readonly string $refreshToken,
        private readonly string $stripeUserId,
        private readonly string $publishableKey,
        private readonly string $scope,
        private readonly bool $livemode,
        private readonly string $tokenType,
        private readonly string $email,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $completedAt = null,
    ) {
    }

    public static function create(
        StripeAccessToken $stripeAccessToken,
        string $email,
    ): self {
        return new self(
            PendingStripeInstallationId::random(),
            self::generateLinkingToken(),
            $stripeAccessToken->accessToken,
            $stripeAccessToken->refreshToken,
            $stripeAccessToken->stripeUserId,
            $stripeAccessToken->publishableKey,
            $stripeAccessToken->scope,
            $stripeAccessToken->livemode,
            $stripeAccessToken->tokenType,
            $email,
            new \DateTimeImmutable('+7 days'),
            new \DateTimeImmutable(),
        );
    }

    private static function generateLinkingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getId(): PendingStripeInstallationId
    {
        return $this->id;
    }

    public function getLinkingToken(): string
    {
        return $this->linkingToken;
    }

    public function getStripeAccessToken(): StripeAccessToken
    {
        return new StripeAccessToken(
            $this->accessToken,
            $this->refreshToken,
            $this->stripeUserId,
            $this->publishableKey,
            $this->scope,
            $this->livemode,
            $this->tokenType,
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function markAsCompleted(): self
    {
        if ($this->isExpired()) {
            throw new \RuntimeException('Installation token has expired');
        }

        if ($this->isCompleted()) {
            throw new \RuntimeException('This installation has already been completed');
        }

        return new self(
            $this->id,
            $this->linkingToken,
            $this->accessToken,
            $this->refreshToken,
            $this->stripeUserId,
            $this->publishableKey,
            $this->scope,
            $this->livemode,
            $this->tokenType,
            $this->email,
            $this->expiresAt,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }
}
