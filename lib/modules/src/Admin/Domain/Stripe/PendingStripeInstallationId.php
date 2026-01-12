<?php
declare(strict_types=1);

namespace App\Admin\Domain\Stripe;


use App\Shared\Domain\ValueObject\Uuid;

final class PendingStripeInstallationId extends Uuid
{
    public static function random(): self
    {
        return new self(parent::random()->value());
    }
}
