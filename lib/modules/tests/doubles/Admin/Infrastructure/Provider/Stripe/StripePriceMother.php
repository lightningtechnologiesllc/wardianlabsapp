<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Provider\Stripe;

use App\Admin\Infrastructure\Provider\Stripe\StripePrice;
use App\Admin\Infrastructure\Provider\Stripe\StripeProduct;

final class StripePriceMother
{
    public static function fixed(): StripePrice
    {
        return new StripePrice(
            'price_1RoVMePOQ7ui3NRxAQv5Jtpc',
            new StripeProduct(
                'prod_123456789',
                'Premium Membership',
                'Access to all premium features'
            ),
            'recurring',
            500,
            'USD ',
            1,
            "month"
        );
    }
}
