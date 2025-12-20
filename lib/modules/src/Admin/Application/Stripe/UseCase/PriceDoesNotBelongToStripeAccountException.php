<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Shared\Domain\Stripe\StripeAccountId;

final class PriceDoesNotBelongToStripeAccountException extends \Exception
{
    public function __construct(private readonly string $priceId, private readonly StripeAccountId $accountId)
    {
        parent::__construct(sprintf('The price with ID %s does not belong to the Stripe account with ID %s.', $this->priceId, $this->accountId));
    }
}
