<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

use App\Core\Types\Collection\Collection;

final class StripeSubscriptions extends Collection
{
    protected function type(): string
    {
        return StripeSubscription::class;
    }

    public static function fromArray(array $items): StripeSubscriptions
    {
        $subscriptions = [];
        foreach ($items as $item) {
            $subscriptions[] = StripeSubscription::fromArray($item);
        }

        return new StripeSubscriptions($subscriptions);
    }

    public function toArray(): array
    {
        return $this->map(function (StripeSubscription $subscription) {
            return $subscription->toArray();
        });
    }
}
