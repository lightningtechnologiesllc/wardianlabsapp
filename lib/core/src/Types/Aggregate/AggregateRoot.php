<?php

declare(strict_types=1);

namespace App\Core\Types\Aggregate;

use App\Core\Messaging\Event;
use App\Core\Types\Identifier\Id as TId;

/**
 * Class AggregateRoot.
 *
 * @template TId of \App\Core\Types\Identifier\Id
 */
abstract class AggregateRoot
{
    /** @var Event[] */
    private array $events = [];

    /**
     * @return TId
     */
    abstract public function id();

    /**
     * Pull events from the aggregate's event collection.
     *
     * @return Event[]
     */
    final public function pullEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    /**
     * Initialize properties of the aggregate instance with properties of the given object.
     */
    final protected function initFrom(object $from): void
    {
        $this->copyProperties($from, $this);
    }

    /**
     * Spawn a new event of the given class copying the common properties from the aggregate instance.
     *
     * @template T of Event
     *
     * @param class-string<T> $className
     *
     * @return Event
     */
    final protected function spawnEvent(string $className)
    {
        $event = new $className();
        $this->copyProperties($this, $event);

        return $event;
    }

    /**
     * Record the given event in the aggregate's events collection.
     */
    final protected function record(Event $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Throw a logic exception from an aggregate root.
     * This won't be needed in PHP where throw statements are expressions.
     *
     * @see https://php.watch/versions/8.0/throw-expressions
     */
    final protected function requiredPropertyException(string $property = null): bool
    {
        throw new \LogicException(sprintf('Missing required property%s%s.', $property ? ' ' : '', $property ?? ''));
    }

    private function copyProperties(object $from, object $to): void
    {
        $fromReflection = new \ReflectionObject($from);
        $properties = (new \ReflectionObject($to))->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();

            if ($fromReflection->hasProperty($propertyName)) {
                $fromProperty = $fromReflection->getProperty($propertyName);
                $fromProperty->setAccessible(true);

                if ($fromProperty->isInitialized($from)) {
                    $property->setValue($to, $fromProperty->getValue($from));
                }
            }
        }
    }
}
