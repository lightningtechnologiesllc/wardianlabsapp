<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Member;

use App\Shared\Domain\ValueObject\Uuid;

final class MemberId extends Uuid
{
    public static function random(): self
    {
        return new self(parent::random()->value());
    }
}
