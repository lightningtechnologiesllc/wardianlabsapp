<?php

declare(strict_types=1);

namespace App\Core\Types\Identifier;

abstract class StringId implements Id
{
    protected string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function equals(Id $id): bool
    {
        return $id instanceof static && $this->id === $id->id;
    }
}
