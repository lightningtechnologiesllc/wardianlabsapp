<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

final readonly class SubscriptionDeletedEvent
{
    public function __construct(
        public string $stripeSubscriptionId,
        public string $stripeCustomerId,
        public string $customerEmail,
    )
    {
    }

    public static function fromStripeEvent(\Stripe\Event $event): self
    {
        $subscription = $event->data->object;

        return new self(
            stripeSubscriptionId: $subscription->id,
            stripeCustomerId: $subscription->customer,
            customerEmail: $subscription->metadata->customer_email ?? '',
        );
    }
}
