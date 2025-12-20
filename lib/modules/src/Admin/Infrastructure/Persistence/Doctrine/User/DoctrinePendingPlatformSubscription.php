<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\User;

use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PlatformSubscription;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'pending_platform_subscriptions')]
#[ORM\Index(name: 'idx_coupon_code', columns: ['coupon_code'])]
#[ORM\Index(name: 'idx_subscription_id', columns: ['subscription_id'])]
class DoctrinePendingPlatformSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(name: 'pending_subscription_id', type: Types::STRING, length: 36, unique: true)]
        private string $pendingSubscriptionId,

        #[ORM\Column(name: 'customer_email', type: Types::STRING, length: 255)]
        private string $customerEmail,

        #[ORM\Column(name: 'coupon_code', type: Types::STRING, length: 20, unique: true)]
        private string $couponCode,

        #[ORM\Column(name: 'subscription_id', type: Types::STRING, length: 255, unique: true)]
        private string $subscriptionId,

        #[ORM\Column(name: 'plan_id', type: Types::STRING, length: 255)]
        private string $planId,

        #[ORM\Column(name: 'status', type: Types::STRING, length: 50)]
        private string $status,

        #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $expiresAt,

        #[ORM\Column(name: 'redeemed', type: Types::BOOLEAN)]
        private bool $redeemed = false,

        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPendingSubscriptionId(): string
    {
        return $this->pendingSubscriptionId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getCouponCode(): string
    {
        return $this->couponCode;
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

    public function isRedeemed(): bool
    {
        return $this->redeemed;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateSubscription(string $planId, string $status, \DateTimeImmutable $expiresAt): void
    {
        $this->planId = $planId;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
    }

    public function markAsRedeemed(): void
    {
        $this->redeemed = true;
    }

    public function updateFromDomain(PendingPlatformSubscription $pending): void
    {
        $this->updateSubscription(
            $pending->getSubscription()->getPlanId(),
            $pending->getSubscription()->getStatus(),
            $pending->getSubscription()->getExpiresAt()
        );

        if ($pending->isRedeemed() !== $this->redeemed) {
            $this->redeemed = $pending->isRedeemed();
        }
    }

    public function toDomain(): PendingPlatformSubscription
    {
        $subscription = new PlatformSubscription(
            subscriptionId: $this->subscriptionId,
            planId: $this->planId,
            status: $this->status,
            expiresAt: $this->expiresAt,
        );

        return new PendingPlatformSubscription(
            id: new PendingPlatformSubscriptionId($this->pendingSubscriptionId),
            customerEmail: $this->customerEmail,
            subscription: $subscription,
            couponCode: $this->couponCode,
            redeemed: $this->redeemed,
            createdAt: $this->createdAt,
        );
    }
}
