<?php
declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Stringable;
use Symfony\Component\Uid\UuidV7;

class Uuid implements Stringable
{
    public function __construct(protected string $value)
    {
        $this->ensureIsValidUuid($value);
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public static function random(): self
    {
        return new static(UuidV7::generate());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Uuid $other): bool
    {
        return $this->value() === $other->value();
    }

    private function ensureIsValidUuid(string $id): void
    {
        if (!UuidV7::isValid($id)) {
            throw new \InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', static::class, $id));
        }
    }
}
