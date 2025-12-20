<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

final class ProductForPriceNotFoundException extends \Exception
{
    public function __construct($id)
    {
        parent::__construct("Product for price id '$id' not found.");
    }
}
