<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

use App\Shared\Domain\Tenant\TenantId;

interface StripeProvider
{
    public function getValidSubscriptionsForUser(string $email, ?TenantId $tenantId = null): StripeSubscriptions;
    public function hasValidSubscription(string $email): bool;
}
