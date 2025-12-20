<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

final readonly class PlatformSubscription
{
    public function __construct(
        private string $subscriptionId,
        private string $planId,
        private string $status,
        private \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Check if subscription has expired
        return $this->expiresAt > new \DateTimeImmutable();
    }

    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function toArray(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'plan_id' => $this->planId,
            'status' => $this->status,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: $data['subscription_id'],
            planId: $data['plan_id'],
            status: $data['status'],
            expiresAt: new \DateTimeImmutable($data['expires_at']),
        );
    }
}
