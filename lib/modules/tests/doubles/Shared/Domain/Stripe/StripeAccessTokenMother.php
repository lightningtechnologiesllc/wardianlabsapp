<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Shared\Domain\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;

final class StripeAccessTokenMother
{
    public static function random(): StripeAccessToken
    {
        return new StripeAccessToken(
            'sk_test' . bin2hex(random_bytes(16)),
            'rk_test' . bin2hex(random_bytes(16)),
            'whsec_' . bin2hex(random_bytes(16)),
            'pk_test' . bin2hex(random_bytes(16)),
            "stripe_apps",
            false,
            "bearer"
        );
    }
}
