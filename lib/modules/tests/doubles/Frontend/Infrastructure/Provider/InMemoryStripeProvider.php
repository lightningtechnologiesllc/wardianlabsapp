<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Stripe\StripeProvider;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\Tenant\TenantId;

final readonly class InMemoryStripeProvider implements StripeProvider
{
    public function __construct(
        private array $subscriptions = []
    )
    {
    }

    public function hasValidSubscription(string $email): bool
    {
        return !$this->getValidSubscriptionsForUser($email)->isEmpty();
    }

    public function getValidSubscriptionsForUser(string $email, ?TenantId $tenantId = null): StripeSubscriptions
    {
        if (!isset($this->subscriptions[$email])) {
            return new StripeSubscriptions([]);
        }

        return new StripeSubscriptions(
            $this->subscriptions[$email]->filter(function (StripeSubscription $subscription) use ($email) {
                return $subscription->isActive();
            })
        );
    }
}
