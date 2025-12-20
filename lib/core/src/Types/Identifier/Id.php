<?php

declare(strict_types=1);

namespace App\Core\Types\Identifier;

interface Id extends \Stringable
{
    public function equals(Id $id): bool;
}
