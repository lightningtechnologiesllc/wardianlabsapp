<?php

declare(strict_types=1);

namespace App\Core\Types\Collection;

class InvalidClassException extends \Exception
{
    public function __construct(private readonly string $expectedClass, private readonly string $givenClass)
    {
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return sprintf('invalid_class_given');
    }

    public function errorMessage(): string
    {
        return sprintf('Invalid class given %s, expect %s', $this->givenClass, $this->expectedClass);
    }
}
