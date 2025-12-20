<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

use App\Core\Types\Collection\Collection;
use App\Frontend\Domain\Discord\GuildId;

final class TenantPriceToRolesMappings extends Collection
{
    public function findOneByGuildId(GuildId $guildId): ?TenantPriceToRolesMapping
    {
        /** @var TenantPriceToRolesMapping $mapping */
        foreach ($this->items as $mapping) {
            if ($mapping->getGuildId()->equals($guildId)) {
                return $mapping;
            }
        }

        return null;
    }

    protected function type(): string
    {
        return TenantPriceToRolesMapping::class;
    }

    public static function fromArray(array $items): TenantPriceToRolesMappings
    {
        $mappings = [];
        foreach ($items as $data) {
            $mappings[] = TenantPriceToRolesMapping::fromArray($data);
        }

        return new TenantPriceToRolesMappings($mappings);
    }

    public function toArray(): array
    {
        return $this->map(function (TenantPriceToRolesMapping $mapping) {
            return $mapping->toArray();
        });
    }
}
