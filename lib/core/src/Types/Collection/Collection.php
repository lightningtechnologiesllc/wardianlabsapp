<?php
declare(strict_types=1);

namespace App\Core\Types\Collection;

use IteratorAggregate;

/**
 * @template T
 *
 * @implements IteratorAggregate<T>
 */
abstract class Collection implements \Countable, \IteratorAggregate
{
    protected \ArrayObject $items;

    /**
     * @param T[] $items
     */
    public function __construct(array $items = [])
    {
        array_walk($items, function ($item) {
            $this->guardAgainstWrongType($item);
        });

        $this->items = new \ArrayObject($items);
    }

    /**
     * @param T $item
     */
    public function add($item): void
    {
        $this->guardAgainstWrongType($item);

        $this->items->append($item);
        $this->items = clone $this->items;
    }

    public function exists($anItem): bool
    {
        $exists = false;
        foreach ($this as $item) {
            if ($item->equals($anItem)) {
                $exists = true;

                break;
            }
        }

        return $exists;
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->items->getIterator();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function getLastItems(int $numberOfItems = -1): array
    {
        $this->orderByCreatedDate();

        if (-1 == $numberOfItems) {
            $numberOfItems = count($this->items);
        }

        return array_slice($this->items->getArrayCopy(), -$numberOfItems, $numberOfItems, false);
    }

    public function orderByCreatedDate(): void
    {
        $this->items->uasort(function ($item1, $item2) {
            return $item1->getCreatedAt() <=> $item2->getCreatedAt();
        });
    }

    /**
     * @return T
     */
    public function first()
    {
        if ($this->isEmpty()) {
            throw new \RuntimeException('Cannot get first item from an empty collection');
        }

        return $this->items->offsetGet(0);
    }

    public function filter(\Closure $param): array
    {
        return array_values(array_filter($this->items->getArrayCopy(), $param));
    }

    public function map(\Closure $param): array
    {
        return array_map($param, $this->items->getArrayCopy());
    }

    public function merge(self $collection): void
    {
        $this->items = new \ArrayObject(array_merge($this->items->getArrayCopy(), $collection->toArray()));
    }

    abstract protected function type(): string;

    /**
     * @param T $item
     */
    private function guardAgainstWrongType($item): void
    {
        $type = $this->type();
        if (!$item instanceof $type) {
            throw new InvalidClassException($type, get_class($item));
        }
    }

    abstract public static function fromArray(array $items): self;
    abstract public function toArray(): array;
}
