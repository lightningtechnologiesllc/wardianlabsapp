<?php
declare(strict_types=1);

namespace App\Shared\Domain;

interface Store
{
    public function save(string $key, array $data): void;

    public function get(string $key): ?array;

    public function delete(string $key): void;
}
