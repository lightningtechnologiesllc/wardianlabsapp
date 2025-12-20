<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

use App\Core\Types\Aggregate\AggregateRoot;

final class PendingPlatformSubscription extends AggregateRoot
{
    public function __construct(
        private readonly PendingPlatformSubscriptionId $id,
        private readonly string $customerEmail,
        private PlatformSubscription $subscription,
        private readonly string $couponCode,
        private bool $redeemed = false,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public static function create(
        PendingPlatformSubscriptionId $id,
        string $customerEmail,
        PlatformSubscription $subscription,
    ): self {
        $couponCode = self::generateCouponCode();

        $pending = new self(
            id: $id,
            customerEmail: $customerEmail,
            subscription: $subscription,
            couponCode: $couponCode,
        );

        // Record domain event
        $pending->record(new PlatformSubscriptionCouponGenerated(
            customerEmail: $customerEmail,
            couponCode: $couponCode,
            subscriptionId: $subscription->getSubscriptionId(),
            planId: $subscription->getPlanId(),
        ));

        return $pending;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: new PendingPlatformSubscriptionId($data['id']),
            customerEmail: $data['customer_email'],
            subscription: PlatformSubscription::fromArray($data['subscription']),
            couponCode: $data['coupon_code'],
            redeemed: $data['redeemed'] ?? false,
            createdAt: new \DateTimeImmutable($data['created_at']),
        );
    }

    public function id(): PendingPlatformSubscriptionId
    {
        return $this->id;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getSubscription(): PlatformSubscription
    {
        return $this->subscription;
    }

    public function getCouponCode(): string
    {
        return $this->couponCode;
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed;
    }

    public function markAsRedeemed(): void
    {
        $this->redeemed = true;
    }

    public function updateSubscription(PlatformSubscription $subscription): void
    {
        $this->subscription = $subscription;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'customer_email' => $this->customerEmail,
            'subscription' => $this->subscription->toArray(),
            'coupon_code' => $this->couponCode,
            'redeemed' => $this->redeemed,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private static function generateCouponCode(): string
    {
        // Generate a random 12-character alphanumeric code (uppercase)
        // Format: XXXX-XXXX-XXXX for readability
        $parts = [];
        for ($i = 0; $i < 3; $i++) {
            $parts[] = strtoupper(bin2hex(random_bytes(2)));
        }
        return implode('-', $parts);
    }
}
