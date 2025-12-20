<?php
declare(strict_types=1);

namespace Tests\Unit\App\Frontend\Infrastructure\Provider;

use App\Frontend\Infrastructure\Provider\RepositoryTenantProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\Tenant\TenantMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryTenantRepository;
use Tests\Doubles\App\Frontend\Infrastructure\Extractor\InMemoryTenantHostExtractor;

#[CoversClass(RepositoryTenantProvider::class)]
final class RepositoryTenantProviderTest extends TestCase
{
    public function testExtractSubdomainWithTwoDots(): void
    {
        $tenant = TenantMother::random();
        $tenantRepository = new InMemoryTenantRepository();
        $provider = new RepositoryTenantProvider(
            new InMemoryTenantHostExtractor("{$tenant->getSubdomain()}.myapp.com"),
            $tenantRepository,
        );
        $tenantRepository->save($tenant);

        $gotTenant = $provider->get();

        $this->assertNotNull($tenant);
        $this->assertEquals($tenant->getSubdomain(), $gotTenant->getSubdomain());
    }
}
