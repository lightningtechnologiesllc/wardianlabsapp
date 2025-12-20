<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

use App\Shared\Domain\ValueObject\Uuid;

final class PendingPlatformSubscriptionId extends Uuid
{
    public static function generate(): self
    {
        return new self(parent::random()->value());
    }
}
