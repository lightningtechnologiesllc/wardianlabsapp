<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

interface TenantRepository
{
    public function save(Tenant $tenant): void;
    public function findOneBySubdomain(string $subdomain): ?Tenant;
    public function findById(\App\Shared\Domain\Tenant\TenantId $tenantId): ?Tenant;
}
