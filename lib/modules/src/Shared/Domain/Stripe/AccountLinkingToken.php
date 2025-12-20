<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Shared\Domain\Tenant\TenantId;

final class AccountLinkingToken
{
    public function __construct(
        private readonly StripeCustomerSubscriptionId $id,
        private readonly TenantId $tenantId,
        private readonly string $stripeSubscriptionId,
        private readonly string $customerEmail,
        private readonly string $stripePriceId,
        private readonly string $linkingToken,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly \DateTimeImmutable $createdAt,
        private ?string $discordUserId = null,
        private ?\DateTimeImmutable $linkedAt = null,
    )
    {
    }

    public static function create(
        TenantId $tenantId,
        string $stripeSubscriptionId,
        string $customerEmail,
        string $stripePriceId
    ): self
    {
        return new self(
            StripeCustomerSubscriptionId::random(),
            $tenantId,
            $stripeSubscriptionId,
            $customerEmail,
            $stripePriceId,
            self::generateLinkingToken(),
            new \DateTimeImmutable('+7 days'), // Token expires in 7 days
            new \DateTimeImmutable(),
        );
    }

    private static function generateLinkingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getId(): StripeCustomerSubscriptionId
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getStripeSubscriptionId(): string
    {
        return $this->stripeSubscriptionId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getStripePriceId(): string
    {
        return $this->stripePriceId;
    }

    public function getLinkingToken(): string
    {
        return $this->linkingToken;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDiscordUserId(): ?string
    {
        return $this->discordUserId;
    }

    public function getLinkedAt(): ?\DateTimeImmutable
    {
        return $this->linkedAt;
    }

    public function isLinked(): bool
    {
        return $this->discordUserId !== null && $this->linkedAt !== null;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function linkToDiscordUser(string $discordUserId): self
    {
        if ($this->isExpired()) {
            throw new \RuntimeException('Linking token has expired');
        }

        if ($this->isLinked()) {
            throw new \RuntimeException('This subscription is already linked to a Discord account');
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->stripeSubscriptionId,
            $this->customerEmail,
            $this->stripePriceId,
            $this->linkingToken,
            $this->expiresAt,
            $this->createdAt,
            $discordUserId,
            new \DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'tenant_id' => $this->tenantId->value(),
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'customer_email' => $this->customerEmail,
            'stripe_price_id' => $this->stripePriceId,
            'linking_token' => $this->linkingToken,
            'discord_user_id' => $this->discordUserId,
            'linked_at' => $this->linkedAt?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
