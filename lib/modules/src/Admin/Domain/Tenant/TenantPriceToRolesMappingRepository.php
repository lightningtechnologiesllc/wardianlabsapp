<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

use App\Shared\Domain\Tenant\TenantId;

interface TenantPriceToRolesMappingRepository
{
    public function save(TenantPriceToRolesMapping $mapping): void;
    public function findByTenant(TenantId $tenantId): TenantPriceToRolesMappings;
    public function findAll(): TenantPriceToRolesMappings;
}
