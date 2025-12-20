<?php
declare(strict_types=1);

namespace Tests\Integration\App\Admin\Infrastructure\Persistence\InMemory\Tenant;

use App\Admin\Domain\Tenant\TenantRepository;
use App\Admin\Domain\User\UserRepository;
use App\Admin\Infrastructure\Persistence\Doctrine\Tenant\DoctrineTenantRepository;
use App\Admin\Infrastructure\Persistence\Doctrine\User\DoctrineUserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Doubles\App\Admin\Domain\Tenant\TenantMother;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(DoctrineTenantRepository::class)]
final class DoctrineTenantRepositoryTest extends IntegrationTestCase
{
    public function testWeCanFindASavedTenant(): void
    {
        $repository = $this->getRepository();
        $owner = UserMother::random();
        $this->getUserRepository()->save($owner);

        $tenant = TenantMother::randomWithOwner($owner);

        $repository->save($tenant);

        $this->assertExists($tenant->getSubdomain());
    }

    public function testWeCanUpdateATenant(): void
    {
        $repository = $this->getRepository();
        $owner = UserMother::random();
        $this->getUserRepository()->save($owner);

        $tenant = TenantMother::randomWithOwner($owner);
        $repository->save($tenant);

        // Update tenant properties
        $faker = \Faker\Factory::create();
        $newName = 'Updated Tenant Name';
        $newSubdomain = 'updated-' . $faker->slug();
        $newEmailDSN = 'smtp://user:pass@smtp.example.com:25';
        $newEmailFromAddress = 'updated@example.com';

        $tenant->updateName($newName);
        $tenant->updateSubdomain($newSubdomain);
        $tenant->updateEmailDSN($newEmailDSN);
        $tenant->updateEmailFromAddress($newEmailFromAddress);

        $repository->save($tenant);

        // Retrieve the tenant and verify updates
        $updatedTenant = $repository->findOneBySubdomain($newSubdomain);

        $this->assertNotNull($updatedTenant);
        $this->assertEquals($newName, $updatedTenant->getName());
        $this->assertEquals($newSubdomain, $updatedTenant->getSubdomain());
        $this->assertEquals($newEmailDSN, $updatedTenant->getEmailDSN());
        $this->assertEquals($newEmailFromAddress, $updatedTenant->getEmailFromAddress());
    }

    private function getRepository(): DoctrineTenantRepository
    {
        return $this->service(TenantRepository::class);
    }

    private function assertExists(string $subdomain)
    {
        $found = $this->getRepository()->findOneBySubdomain($subdomain);
        $this->assertNotNull($found);
        $this->assertEquals($found->getSubdomain(), $subdomain);
    }

    private function getUserRepository(): DoctrineUserRepository
    {

        return $this->service(UserRepository::class);
    }
}
