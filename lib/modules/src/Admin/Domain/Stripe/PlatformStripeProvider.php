<?php
declare(strict_types=1);

namespace App\Admin\Domain\Stripe;

interface PlatformStripeProvider
{
    /**
     * @throws CustomerNotFoundException
     */
    public function getCustomerEmail(string $customerId): string;
}
