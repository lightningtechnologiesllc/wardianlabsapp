<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

final readonly class SubscriptionCreatedEvent
{
    public function __construct(
        public string $stripeSubscriptionId,
        public string $stripeCustomerId,
        public string $stripePriceId,
        public string $customerEmail,
        public string $status,
        public ?int $currentPeriodEnd,
    )
    {
    }

    public static function fromStripeEvent(\Stripe\Event $event): self
    {
        $subscription = $event->data->object;

        // For Stripe Apps, we get customer ID and will fetch email separately
        return new self(
            stripeSubscriptionId: $subscription->id,
            stripeCustomerId: $subscription->customer,
            stripePriceId: $subscription->items->data[0]->price->id,
            customerEmail: '', // Will be fetched from Stripe API
            status: $subscription->status,
            currentPeriodEnd: $subscription->current_period_end,
        );
    }

    public function withCustomerEmail(string $email): self
    {
        return new self(
            stripeSubscriptionId: $this->stripeSubscriptionId,
            stripeCustomerId: $this->stripeCustomerId,
            stripePriceId: $this->stripePriceId,
            customerEmail: $email,
            status: $this->status,
            currentPeriodEnd: $this->currentPeriodEnd,
        );
    }
}
