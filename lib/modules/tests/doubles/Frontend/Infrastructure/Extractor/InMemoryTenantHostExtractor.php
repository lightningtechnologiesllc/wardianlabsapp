<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Infrastructure\Extractor;

use App\Frontend\Domain\Extractor\TenantHostExtractor;

final readonly class InMemoryTenantHostExtractor implements TenantHostExtractor
{
    public function __construct(
        private string $tenantHost
    ) {
    }

    public function extract(): string
    {
        return $this->tenantHost;
    }
}
