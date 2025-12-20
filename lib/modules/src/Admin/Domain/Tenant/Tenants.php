<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

use App\Core\Types\Collection\Collection;

final class Tenants extends Collection
{
    protected function type(): string
    {
        return Tenant::class;
    }

    public static function fromArray(array $items): Tenants
    {
        $tenants = [];
        foreach ($items as $data) {
            $tenants[] = Tenant::fromArray($data);
        }

        return new Tenants($tenants);
    }

    public function toArray(): array
    {
        return $this->map(function (Tenant $tenant) {
            return $tenant->toArray();
        });
    }
}
