<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

use App\Shared\Domain\ValueObject\Uuid;

final class StripeAccountId extends Uuid
{
    public static function random(): self
    {
        return new self(parent::random()->value());
    }
}
