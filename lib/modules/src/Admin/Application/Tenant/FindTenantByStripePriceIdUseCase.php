<?php
declare(strict_types=1);

namespace App\Admin\Application\Tenant;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Admin\Domain\Tenant\TenantRepository;

final readonly class FindTenantByStripePriceIdUseCase
{
    public function __construct(
        private TenantPriceToRolesMappingRepository $priceToRolesMappingRepository,
        private TenantRepository $tenantRepository,
    )
    {
    }

    public function __invoke(string $stripePriceId): ?Tenant
    {
        // Find all price-to-roles mappings
        $allMappings = $this->priceToRolesMappingRepository->findAll();

        // Search for the tenant that has this price configured
        foreach ($allMappings as $mapping) {
            $pricesToRolesMapping = $mapping->getPricesToRolesMapping();

            if (array_key_exists($stripePriceId, $pricesToRolesMapping)) {
                // Found the tenant that has this price configured
                return $this->tenantRepository->findById($mapping->getTenantId());
            }
        }

        return null;
    }
}
