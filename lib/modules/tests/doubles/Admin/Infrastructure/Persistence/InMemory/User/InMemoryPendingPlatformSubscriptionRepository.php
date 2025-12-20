<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User;

use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;

final class InMemoryPendingPlatformSubscriptionRepository implements PendingPlatformSubscriptionRepository
{
    private array $pendingSubscriptions = [];

    public function save(PendingPlatformSubscription $pendingSubscription): void
    {
        foreach ($this->pendingSubscriptions as $key => $existing) {
            if ($existing->id()->equals($pendingSubscription->id())) {
                $this->pendingSubscriptions[$key] = $pendingSubscription;
                return;
            }
        }

        $this->pendingSubscriptions[] = $pendingSubscription;
    }

    public function findByCouponCode(string $couponCode): ?PendingPlatformSubscription
    {
        return array_find(
            $this->pendingSubscriptions,
            fn($pending) => $pending->getCouponCode() === $couponCode
        );
    }

    public function findBySubscriptionId(string $subscriptionId): ?PendingPlatformSubscription
    {
        return array_find(
            $this->pendingSubscriptions,
            fn($pending) => $pending->getSubscription()->getSubscriptionId() === $subscriptionId
        );
    }
}
