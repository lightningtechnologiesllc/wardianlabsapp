<?php
declare(strict_types=1);

namespace Tests\Integration\App\Admin\Infrastructure\Persistence\InMemory\Tenant;

use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
use App\Admin\Infrastructure\Persistence\Doctrine\Tenant\DoctrineTenantPriceToRolesMappingRepository;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\GuildId;
use App\Shared\Domain\Tenant\TenantId;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(DoctrineTenantPriceToRolesMappingRepository::class)]
final class DoctrineTenantPriceToRolesMappingRepositoryTest extends IntegrationTestCase
{
    public function testWeCanFindASavedTenant(): void
    {
        $repository = $this->getRepository();

        $tenantId = TenantId::random();
        $mapping = new TenantPriceToRolesMapping(
            $tenantId,
            GuildId::random(),
            [
                'price_1' => [DiscordRoleId::random()->value(), DiscordRoleId::random()->value()],
                'price_2' => [DiscordRoleId::random()->value()],
            ]
        );

        $repository->save($mapping);

        $this->assertExists($tenantId);
    }

    public function testWeCanUpdateAnExistingTenant(): void
    {
        $repository = $this->getRepository();

        $tenantId = TenantId::random();
        $mapping = new TenantPriceToRolesMapping(
            $tenantId,
            GuildId::random(),
            [
                'price_1' => [DiscordRoleId::random()->value(), DiscordRoleId::random()->value()],
                'price_2' => [DiscordRoleId::random()->value()],
            ]
        );

        $repository->save($mapping);
        $this->assertExists($tenantId);

        $updatedMapping = new TenantPriceToRolesMapping(
            $tenantId,
            GuildId::random(),
            [
                'price_3' => [DiscordRoleId::random()->value()],
            ]
        );

        $repository->save($updatedMapping);
        $found = $this->assertExists($tenantId);
        $this->assertArrayHasKey('price_3', $found->getPricesToRolesMapping());
    }

    private function assertExists(TenantId $tenantId): TenantPriceToRolesMapping
    {
        $foundCollection = $this->getRepository()->findByTenant($tenantId);
        $this->assertFalse($foundCollection->isEmpty());

        $found = $foundCollection->first();
        $this->assertNotNull($found);
        $this->assertEquals($found->getTenantId(), $tenantId);
        return $found;
    }

    private function getRepository(): DoctrineTenantPriceToRolesMappingRepository
    {
        return $this->service(DoctrineTenantPriceToRolesMappingRepository::class);
    }
}
