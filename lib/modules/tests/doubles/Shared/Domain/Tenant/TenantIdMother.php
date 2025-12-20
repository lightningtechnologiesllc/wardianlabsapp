<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Shared\Domain\Tenant;

use App\Shared\Domain\Tenant\TenantId;

final class TenantIdMother
{
    public static function create(?string $value = null): TenantId
    {
        return new TenantId($value ?? TenantId::random()->value());
    }
}
