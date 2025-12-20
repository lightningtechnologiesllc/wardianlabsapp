<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Shared\Domain\Stripe;

use App\Shared\Domain\Stripe\StripeProviderAccount;

final class StripeProviderAccountMother
{
    public static function random(): StripeProviderAccount
    {
        return new StripeProviderAccount(
            stripeProviderAccountId: 'acct_' . bin2hex(random_bytes(16)),
            displayName: 'Test Account',
            accessToken: StripeAccessTokenMother::random(),
        );
    }
}
