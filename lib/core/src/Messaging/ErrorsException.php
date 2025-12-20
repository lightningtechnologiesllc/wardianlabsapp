<?php

declare(strict_types=1);

namespace App\Core\Messaging;

final class ErrorsException extends \InvalidArgumentException
{
    /** @var array<string, \Throwable> */
    private array $errors;
    private string $className;

    public function __construct(string $className)
    {
        $this->errors = [];
        $this->className = $className;
        parent::__construct('Errors in message');
    }

    public function addErrorByProperty(string $property, \Throwable $item): void
    {
        $this->errors[$property] = $item;
        $this->message = $this->getErrorListToString();
    }

    public function count(): int
    {
        return count($this->errors);
    }

    public function hasPropertyError(string $property): bool
    {
        return isset($this->errors[$property]);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->getErrorsList();
    }

    /**
     * @return mixed[]
     */
    private function getErrorsList(): array
    {
        $generalMessage = sprintf('Found %s error/s in command %s', $this->count(), $this->className);
        $errors = ['message' => $generalMessage, 'errors' => []];

        foreach ($this->errors as $property => $error) {
            $errors['errors'][] = ['property' => $property, 'message' => $error->getMessage()];
        }

        return $errors;
    }

    private function getErrorListToString(): string
    {
        $errorList = $this->getErrorsList();
        $errorMessage = $errorList['message'];
        $errors = array_reduce($errorList['errors'], function ($carry, $item) {
            return (empty($carry)) ? $item['message'] : sprintf('%s | %s', $carry, $item['message']);
        }, '');

        return sprintf('%s. Errors: %s', $errorMessage, $errors);
    }
}
