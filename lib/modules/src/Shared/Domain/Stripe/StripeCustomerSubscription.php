<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Shared\Domain\Tenant\TenantId;

final class StripeCustomerSubscription
{
    public function __construct(
        private readonly StripeCustomerSubscriptionId $id,
        private TenantId $tenantId,
        private string $stripeSubscriptionId,
        private string $stripeCustomerId,
        private string $customerEmail,
        private string $stripePriceId,
        private string $status,
        private int $currentPeriodEnd,
        private ?string $linkingToken = null,
        private ?string $discordUserId = null,
        private ?\DateTimeImmutable $linkedAt = null,
    )
    {
    }

    public static function fromStripeEvent(
        TenantId $tenantId,
        string $stripeSubscriptionId,
        string $stripeCustomerId,
        string $customerEmail,
        string $stripePriceId,
        string $status,
        int $currentPeriodEnd
    ): self
    {
        return new self(
            StripeCustomerSubscriptionId::random(),
            $tenantId,
            $stripeSubscriptionId,
            $stripeCustomerId,
            $customerEmail,
            $stripePriceId,
            $status,
            $currentPeriodEnd,
            self::generateLinkingToken(),
            null,
            null
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

    public function getStripeCustomerId(): string
    {
        return $this->stripeCustomerId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getStripePriceId(): string
    {
        return $this->stripePriceId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCurrentPeriodEnd(): int
    {
        return $this->currentPeriodEnd;
    }

    public function getLinkingToken(): ?string
    {
        return $this->linkingToken;
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

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }

    public function updateCurrentPeriodEnd(int $currentPeriodEnd): void
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
    }

    public function linkToDiscordUser(string $discordUserId): void
    {
        $this->discordUserId = $discordUserId;
        $this->linkedAt = new \DateTimeImmutable();
        $this->linkingToken = null; // Invalidate token after linking
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'tenant_id' => $this->tenantId->value(),
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'stripe_customer_id' => $this->stripeCustomerId,
            'customer_email' => $this->customerEmail,
            'stripe_price_id' => $this->stripePriceId,
            'status' => $this->status,
            'current_period_end' => $this->currentPeriodEnd,
            'linking_token' => $this->linkingToken,
            'discord_user_id' => $this->discordUserId,
            'linked_at' => $this->linkedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
