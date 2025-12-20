<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Domain\Tenant;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\User\User;
use Tests\Doubles\App\Shared\Domain\Tenant\TenantIdMother;

final class TenantMother
{
    public static function random(): Tenant
    {
        $faker = \Faker\Factory::create();

        return new Tenant(
            TenantIdMother::create(),
            "Test Tenant",
            $faker->domainWord(),
            '',
            $faker->email(),
        );
    }
    public static function randomWithOwner(User $owner): Tenant
    {
        $faker = \Faker\Factory::create();
        $tenant = new Tenant(
            TenantIdMother::create(),
            "Test Tenant",
            $faker->slug(),
            '',
            $faker->email(),
        );
        $owner->addTenant($tenant);
        return $tenant;
    }
}
