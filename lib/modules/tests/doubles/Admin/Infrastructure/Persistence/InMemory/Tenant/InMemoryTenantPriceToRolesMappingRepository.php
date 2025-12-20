<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\Tenant;

use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappings;
use App\Shared\Domain\Tenant\TenantId;

final class InMemoryTenantPriceToRolesMappingRepository implements TenantPriceToRolesMappingRepository
{
    private array $mappings = [];

    public function save(TenantPriceToRolesMapping $mapping): void
    {
        /** @var TenantPriceToRolesMapping $existingMapping */
        foreach ($this->mappings as $key => $existingMapping) {
            if ($existingMapping->getTenantId()->equals($mapping->getTenantId())) {
                $this->mappings[$key] = $mapping;
                return;
            }
        }

        $this->mappings[] = $mapping;
    }

    public function findByTenant(TenantId $tenantId): TenantPriceToRolesMappings
    {
        $foundMappings = array_filter(
            $this->mappings,
            fn (TenantPriceToRolesMapping $mapping) => $mapping->getTenantId()->equals($tenantId)
        );

        return new TenantPriceToRolesMappings(array_values($foundMappings));
    }

    public function findAll(): TenantPriceToRolesMappings
    {
        return new TenantPriceToRolesMappings($this->mappings);
    }
}
