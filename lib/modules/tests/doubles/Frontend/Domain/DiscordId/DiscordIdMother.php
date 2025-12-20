<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Domain\DiscordId;

use App\Frontend\Domain\Discord\DiscordId;

final class DiscordIdMother
{
    public static function create(?string $value = null): DiscordId
    {
        return new DiscordId($value ?? DiscordId::random()->value());
    }
}
