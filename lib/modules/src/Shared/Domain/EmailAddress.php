<?php
declare(strict_types=1);

namespace App\Shared\Domain;

final readonly class EmailAddress
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid email address.',
                $value
            ));
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
