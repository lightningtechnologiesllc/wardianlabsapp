<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

use App\Core\Messaging\Event;

final class PlatformSubscriptionCouponGenerated extends Event
{
    public function __construct(
        public readonly string $customerEmail = '',
        public readonly string $couponCode = '',
        public readonly string $subscriptionId = '',
        public readonly string $planId = '',
    ) {
    }
}
