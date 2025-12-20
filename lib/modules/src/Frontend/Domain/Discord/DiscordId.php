<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Discord;

class DiscordId
{
    public function __construct(private readonly string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Discord ID cannot be empty.');
        }

        if (!ctype_digit($value)) {
            throw new \InvalidArgumentException('Invalid Discord ID format.');
        }
    }

    public static function random(): static
    {
        return new static((string) rand(1000000000000000, 9999999999999999));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(DiscordId $other): bool
    {
        return $this->value() === $other->value();
    }
}
