<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Provider\Stripe;

use App\Admin\Infrastructure\Provider\Stripe\AccountStripeProvider;
use App\Admin\Infrastructure\Provider\Stripe\StripePrices;
use App\Shared\Domain\Stripe\StripeAccount;

final class InMemoryAccountStripeProvider implements AccountStripeProvider
{
    private array $accountsPrices = [];

    public function getPricesForAccount(StripeAccount $account): StripePrices
    {
        return $this->accountsPrices[$account->getAccountId()->value()] ?? throw new \Exception("Unexpecte error");
    }

    public function addPricesForAccount(StripeAccount $account, StripePrices $prices): void
    {
        $this->accountsPrices[$account->getAccountId()->value()] = $prices;
    }
}
