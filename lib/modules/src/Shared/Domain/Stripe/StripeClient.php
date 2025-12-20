<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Shared\Domain\Tenant\TenantId;

interface StripeClient
{
    public function retrieveAccount(StripeAccessToken $accessToken, TenantId $tenantId): StripeProviderAccount;
}
