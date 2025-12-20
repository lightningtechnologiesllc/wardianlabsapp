<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Shared\Domain\Tenant\TenantId;

final class StripeAccount
{
    public function __construct(
        private readonly StripeAccountId $accountId,
        private TenantId                 $tenantId,
        private string                   $stripeProviderAccountId,
        private string                   $displayName,
        private StripeAccessToken        $accessToken,
        private ?string                  $webhookSecret = null,
        private ?string                  $webhookEndpointId = null,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            new StripeAccountId($data['account_id']),
            new TenantId($data['tenant_id']),
            $data['stripe_account_id'],
            $data['display_name'],
            StripeAccessToken::fromArray($data['access_token']),
            $data['webhook_secret'] ?? null,
            $data['webhook_endpoint_id'] ?? null,
        );
    }

    public function getAccountId(): StripeAccountId
    {
        return $this->accountId;
    }

    public function getStripeProviderAccountId(): string
    {
        return $this->stripeProviderAccountId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getAccessToken(): StripeAccessToken
    {
        return $this->accessToken;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function setTenantId(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function setStripeProviderAccountId(string $stripeProviderAccountId): void
    {
        $this->stripeProviderAccountId = $stripeProviderAccountId;
    }

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function setAccessToken(StripeAccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    public function setWebhookSecret(?string $webhookSecret): void
    {
        $this->webhookSecret = $webhookSecret;
    }

    public function getWebhookEndpointId(): ?string
    {
        return $this->webhookEndpointId;
    }

    public function setWebhookEndpointId(?string $webhookEndpointId): void
    {
        $this->webhookEndpointId = $webhookEndpointId;
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId->value(),
            'tenant_id' => $this->tenantId->value(),
            'stripe_account_id' => $this->stripeProviderAccountId,
            'display_name' => $this->displayName,
            'access_token' => $this->accessToken->toArray(),
            'webhook_secret' => $this->webhookSecret,
            'webhook_endpoint_id' => $this->webhookEndpointId,
        ];
    }
}
