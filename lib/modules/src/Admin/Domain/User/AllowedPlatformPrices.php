<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

final readonly class AllowedPlatformPrices
{
    /** @var string[] */
    private array $prices;

    public function __construct(string $pricesEnv)
    {
        $this->prices = array_filter(
            array_map('trim', explode(',', $pricesEnv)),
            fn(string $price) => $price !== ''
        );
    }

    public function isAllowed(string $priceId): bool
    {
        return in_array($priceId, $this->prices, true);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return $this->prices;
    }
}
