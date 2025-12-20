<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

enum StripePriceType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';
}
