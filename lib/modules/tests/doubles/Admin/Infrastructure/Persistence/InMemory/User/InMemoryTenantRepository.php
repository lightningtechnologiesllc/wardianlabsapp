<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\TenantRepository;

final class InMemoryTenantRepository implements TenantRepository
{
    private array $tenants = [];

    public function save(Tenant $tenant): void
    {
        /** @var Tenant $existingTenant */
        foreach ($this->tenants as $key => $existingTenant) {
            if ($existingTenant->getId()->equals($tenant->getId())) {
                $this->tenants[$key] = $tenant;
                return;
            }
        }

        $this->tenants[] = $tenant;
    }

    public function findOneBySubdomain(string $subdomain): ?Tenant
    {
        return array_find($this->tenants, fn(Tenant $tenant) => $tenant->getSubdomain() === $subdomain);
    }

    public function findById(\App\Shared\Domain\Tenant\TenantId $tenantId): ?Tenant
    {
        return array_find($this->tenants, fn(Tenant $tenant) => $tenant->getId()->equals($tenantId));
    }
}
