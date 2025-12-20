<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Provider;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\TenantRepository;
use App\Frontend\Domain\Extractor\TenantHostExtractor;
use App\Frontend\Domain\Tenant\TenantProvider;

final readonly class RepositoryTenantProvider implements TenantProvider
{
    public function __construct(
        private TenantHostExtractor $tenantHostExtractor,
        private TenantRepository    $tenantRepository,
    )
    {
    }

    public function get(): Tenant
    {
        $host = $this->tenantHostExtractor->extract();

        $subdomain = $this->extractSubdomain($host);

        $tenant = $this->tenantRepository->findOneBySubdomain($subdomain);

        if ($tenant === null) {
            throw new \Exception("Tenant config not found for subdomain: $subdomain");
        }

        return $tenant;
    }

    // Extract subdomain from host. E.g. for host "tenant.example.com", return "tenant".
    public function extractSubdomain(string $host): string
    {
        $parts = explode('.', $host);
        return $parts[0];
    }
}
