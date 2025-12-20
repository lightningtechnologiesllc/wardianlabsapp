<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Core\Types\Collection\Collection;

final class StripeAccounts extends Collection
{
    protected function type(): string
    {
        return StripeAccount::class;
    }

    public static function fromArray(array $items): StripeAccounts
    {
        $accounts = [];
        foreach ($items as $itemData) {
            $accounts[] = StripeAccount::fromArray($itemData);
        }

        return new StripeAccounts($accounts);
    }

    public function toArray(): array
    {
        return $this->map(function (StripeAccount $stripeAccount) {
            return $stripeAccount->toArray();
        });
    }
}
