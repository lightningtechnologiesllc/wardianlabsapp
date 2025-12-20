<?php

declare(strict_types=1);

namespace App\Core\Types\Identifier;

/**
 * ObjectId provides a base class for identifier values based on MongoDB Object ID specification.
 * Internally it uses \MongoDB\BSON\ObjectId but this fact is partially hidden to clients.
 * Coupling to \MongoDB\BSON\ObjectId at this point is a trade-off that favors pragmatism whn integrating/refactoring
 * legacy codebase and to avoid dealing back and forth with raw strings.
 */
abstract class ObjectId implements Id
{
    protected \MongoDB\BSON\ObjectId $id;

    /**
     * @param null|\MongoDB\BSON\ObjectId|string $id
     */
    public function __construct($id = null)
    {
        if ($id instanceof \MongoDB\BSON\ObjectId) {
            $this->id = $id;

            return;
        }

        if (null != $id && is_string($id)) {
            $this->id = new \MongoDB\BSON\ObjectId($id);

            return;
        }

        $this->id = new \MongoDB\BSON\ObjectId();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Returns whether the given ID is accepted by this type.
     *
     * @param mixed $id
     */
    public static function accepts($id): bool
    {
        return
            ($id instanceof \MongoDB\BSON\ObjectId)
            || (is_string($id) && 24 === strlen($id) && ctype_xdigit($id));
    }

    public function equals(Id $id): bool
    {
        return (string) $this->id === (string) $id;
    }

    /**
     * It returns a non valid id based on timestamp. It's useful for using it in DB queries. e.g dealing with DB document which doesn't have a date field.
     */
    public static function createFromDate(\DateTimeImmutable $date): \MongoDB\BSON\ObjectId
    {
        if ($date->getTimestamp() < 0) {
            throw new \InvalidArgumentException('Cannot convert timestamps < 0');
        }

        return new \MongoDB\BSON\ObjectId(sprintf('%08x%016x', $date->getTimestamp(), 0));
    }

    /**
     * It returns a valid Date from current objectid.
     */
    public function toDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->id->getTimestamp());
    }

    /**
     * Caution! Use this method at your discretion. In most cases it is better to use the string representation.
     * We expose it to deal with some legacy entities that are complex to hydrate and persist from the infrastructure.
     */
    public function toObjectId(): \MongoDB\BSON\ObjectId
    {
        return $this->id;
    }
}
