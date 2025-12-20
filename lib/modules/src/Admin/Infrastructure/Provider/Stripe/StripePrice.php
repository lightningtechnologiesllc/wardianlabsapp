<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

use Stripe\Collection;

final class StripePrice
{
    public function __construct(
        private string $id,
        private StripeProduct $product,
        private string $type,
        private int $unitAmount,
        private string $currency,
        private ?int $intervalCount = null,
        private ?string $interval = null,
    ) {
        if (!in_array($type, array_column(StripePriceType::cases(), 'value'))) {
            throw new \InvalidArgumentException("Invalid price type: $type");
        }
    }

    public function getFormattedUnitAmount(): string
    {
        return number_format($this->unitAmount / 100, 2);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProduct(): StripeProduct
    {
        return $this->product;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUnitAmount(): int
    {
        return $this->unitAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIntervalCount(): ?int
    {
        return $this->intervalCount;
    }

    public function getInterval(): ?string
    {
        return $this->interval;
    }
}
