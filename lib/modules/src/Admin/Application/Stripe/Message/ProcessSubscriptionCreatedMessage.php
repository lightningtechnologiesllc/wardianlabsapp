<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\Message;

final readonly class ProcessSubscriptionCreatedMessage
{
    public function __construct(
        public array $subscriptionData,
    ) {
    }
}
