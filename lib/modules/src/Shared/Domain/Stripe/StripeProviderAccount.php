<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

final class StripeProviderAccount
{
    public function __construct(
        public string            $stripeProviderAccountId,
        public string            $displayName,
        public StripeAccessToken $accessToken,
    )
    {
    }
}
