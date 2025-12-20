<?php

declare(strict_types=1);

namespace App\Core\Types\Identifier;

use Ulid\Ulid as BaseUlid;

abstract class Ulid implements Id
{
    private BaseUlid $id;

    /**
     * @param null|BaseUlid|string $id
     */
    final public function __construct($id = null)
    {
        $this->id = $this->ulidFrom($id);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public static function generateAsString(): string
    {
        return (string) self::generateBaseUlid();
    }

    /**
     * @return static
     */
    public static function generate(): self
    {
        return new static(self::generateBaseUlid());
    }

    /**
     * @return static
     */
    public static function fromString(string $id): self
    {
        return new static(BaseUlid::fromString($id, true));
    }

    /**
     * @return static
     */
    public static function fromTimestamp(int $milliseconds): self
    {
        return new static(BaseUlid::fromTimestamp($milliseconds, true));
    }

    public function equals(Id $other): bool
    {
        return $other instanceof static && ((string) $this->id) === (string) $other->id;
    }

    public function toTimestamp(): int
    {
        return $this->id->toTimestamp();
    }

    /**
     * @param mixed $id
     */
    private function ulidFrom($id): BaseUlid
    {
        if (null === $id) {
            return BaseUlid::generate(true);
        }

        if ($id instanceof BaseUlid) {
            return $id;
        }

        if (is_string($id)) {
            return BaseUlid::fromString($id, true);
        }

        throw new \InvalidArgumentException(sprintf('Invalid ulid: %s', $id));
    }

    private static function generateBaseUlid(): BaseUlid
    {
        return BaseUlid::generate(true);
    }
}
