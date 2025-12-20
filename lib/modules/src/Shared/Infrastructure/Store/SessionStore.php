<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Store;

use App\Shared\Domain\Store;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class SessionStore implements Store
{
    public function __construct(
        private RequestStack $requestStack,
    )
    {
    }

    public function save(string $key, array $data): void
    {
        $this->requestStack->getSession()->set($key, $data);
    }

    public function get(string $key): ?array
    {
        $data = $this->requestStack->getSession()->get($key);
        if ($data === null) {
            return null;
        }

        return $data;
    }

    public function delete(string $key): void
    {
        $this->requestStack->getSession()->remove($key);
    }
}
