<?php
declare(strict_types=1);

namespace App\Shared\Domain\Tenant;

use App\Shared\Domain\ValueObject\Uuid;

final class TenantId extends Uuid
{
    public static function random(): self
    {
        return new self(parent::random()->value());
    }
}
