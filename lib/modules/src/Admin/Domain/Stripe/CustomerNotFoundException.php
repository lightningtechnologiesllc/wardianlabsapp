<?php
declare(strict_types=1);

namespace App\Admin\Domain\Stripe;

final class CustomerNotFoundException extends \RuntimeException
{
    public function __construct(string $customerId)
    {
        parent::__construct(sprintf('Customer "%s" not found in platform Stripe', $customerId));
    }
}
