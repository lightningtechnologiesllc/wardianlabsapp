<?php

declare(strict_types=1);

namespace App\Core\Messaging;

use App\Core\Messaging\ErrorsException;

/**
 * Represents a message (usually a command, a query, or an event) that can be handled by a message bus.
 */
abstract class Message
{
    private const PRIMITIVE_TYPES = ['int', 'string', 'bool', 'float', 'array', 'object'];

    /**
     * Error handler.
     */
    private ErrorsException $errors;

    /**
     * @return null|mixed
     *
     * @throws \ReflectionException
     */
    public function __get(string $name)
    {
        if ($this->hasErrors()) {
            throw $this->getErrors();
        }

        if (property_exists(static::class, $name)) {
            $reflection = new \ReflectionProperty($this, $name);
            $reflection->setAccessible(true);

            return $reflection->isInitialized($this) ? $reflection->getValue($this) : null;
        }

        return null;
    }

    /**
     * @param null|mixed $value
     *
     * @throws \ReflectionException
     */
    public function __set(string $name, $value): void
    {
        $prop = new \ReflectionProperty(static::class, $name);
        $prop->setAccessible(true);

        $type = $prop->getType();
        assert($type instanceof \ReflectionNamedType);
        $typeName = $type->getName();

        try {
            if ($this->checkIfIsPrimitive($typeName)) {
                $this->checkIfValueIsSameType($type, $value, $name);
            } elseif (null !== $value && !($value instanceof $typeName)) {
                $value = new $typeName($value);
            }

            $prop->setValue($this, $value);
        } catch (\Throwable $e) {
            $this->getErrors()->addErrorByProperty($name, $e);

            return;
        }
    }

    public function validate(): void
    {
        $reflect = new \ReflectionClass(static::class);
        $props = $reflect->getProperties();

        foreach ($props as $prop) {
            $propertyName = $prop->getName();

            // Check if already we have an error for this property.
            if ($this->getErrors()->hasPropertyError($propertyName)) {
                continue;
            }

            $prop->setAccessible(true);

            if (null !== $prop->getType() && !$prop->getType()->allowsNull() && (!$prop->isInitialized($this) || is_null($prop->getValue($this)))) {
                $error = new \InvalidArgumentException(sprintf('Property %s can\'t be null.', $propertyName));
                $this->getErrors()->addErrorByProperty($propertyName, $error);
            }
        }

        if ($this->hasErrors()) {
            throw $this->getErrors();
        }
    }

    private function hasErrors(): bool
    {
        return (bool) $this->getErrors()->count();
    }

    private function checkIfIsPrimitive(string $typeName): bool
    {
        return in_array($typeName, self::PRIMITIVE_TYPES, true);
    }

    /**
     * @param null|mixed $value
     */
    private function checkIfValueIsSameType(\ReflectionType $type, $value, string $propertyName): void
    {
        $valueType = $this->normalizeType(gettype($value));
        assert($type instanceof \ReflectionNamedType);

        if ($valueType !== $type->getName() && ('null' !== $valueType || !$type->allowsNull())) {
            throw new \Error(sprintf('%s must be %s, %s given.', $propertyName, $type->getName(), $valueType));
        }
    }

    private function normalizeType(string $type): string
    {
        switch ($type) {
            case 'integer':
                $type = 'int';

                break;

            case 'boolean':
                $type = 'bool';

                break;

            case 'double':
                $type = 'float';

                break;
        }

        return strtolower($type);
    }

    private function getErrors(): ErrorsException
    {
        return $this->errors ?? ($this->errors = new ErrorsException(static::class));
    }
}
