<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

use App\Core\Types\Collection\Collection;

final class StripePrices extends Collection
{
    protected function type(): string
    {
        return StripePrice::class;
    }

    public static function fromArray(array $items): Collection
    {
        throw new \Exception('Not implemented');
    }

    public function toArray(): array
    {
        throw new \Exception('Not implemented');
    }

    public static function fromStripeCollections(\Stripe\Collection $prices, \Stripe\Collection $products): StripePrices
    {
        $stripePrices = new StripePrices();

        foreach ($prices as $price) {
            $product = $products->data[array_search($price->product, array_column($products->data, 'id'))] ?? null;
            if ($product === null) {
                throw new ProductForPriceNotFoundException($price->id);
            }
            $stripeProduct = new StripeProduct(
                id: $product->id,
                name: $product->name,
                description: $product->description,
            );
            $stripePrice = new StripePrice(
                id: $price->id,
                product: $stripeProduct,
                type: $price->type,
                unitAmount: $price->unit_amount,
                currency: $price->currency,
                intervalCount: $price->recurring->interval_count ?? null,
                interval: $price->recurring->interval ?? null,
            );
            $stripePrices->add($stripePrice);
        }
        return $stripePrices;
    }

    public function filterRecurrent(): StripePrices
    {
        return new StripePrices($this->filter(fn(StripePrice $price) => $price->getType() === StripePriceType::Recurring->value));
    }

    public function priceExists(string $priceId): bool
    {
        $filteredStripePrices = $this->filter(function (StripePrice $stripePrice) use ($priceId) {
            return $stripePrice->getId() === $priceId;
        });

        if (count($filteredStripePrices) > 0) {
            return true;
        }

        return false;
    }
}
