<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

interface PendingPlatformSubscriptionRepository
{
    public function save(PendingPlatformSubscription $pendingSubscription): void;

    public function findByCouponCode(string $couponCode): ?PendingPlatformSubscription;

    public function findBySubscriptionId(string $subscriptionId): ?PendingPlatformSubscription;
}
