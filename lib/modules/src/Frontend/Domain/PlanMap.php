<?php
declare(strict_types=1);

namespace App\Frontend\Domain;

final class PlanMap
{
    public function __construct(
        private array $data = []
    ) {
    }

    public function getRoleByPlanId(string $priceId): ?string
    {
        return $this->data[$priceId] ?? null;
    }
}
