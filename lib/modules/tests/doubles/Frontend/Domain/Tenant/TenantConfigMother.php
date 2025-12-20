<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Domain\Tenant;

use App\Frontend\Domain\Tenant\TenantConfig;
use Symfony\Component\Uid\UuidV7;

final class TenantConfigMother
{
    public static function withStripeApiKey(string $stripeApiKey): TenantConfig
    {
        return new TenantConfig(
            UuidV7::generate(),
            "test.local",
            "123456789",
            $stripeApiKey,
            "smtp://localhost",
            "test@techabreath.com",
            "subject prefix",
        );
    }
}
