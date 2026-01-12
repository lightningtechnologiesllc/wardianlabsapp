<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;

interface AccountStripeProvider
{
    public function getPricesForAccount(StripeAccount $account): StripePrices;

    public function getAccountEmail(StripeAccessToken $accessToken): ?string;
}
