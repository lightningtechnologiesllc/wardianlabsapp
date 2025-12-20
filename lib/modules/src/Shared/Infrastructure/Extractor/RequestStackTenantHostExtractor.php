<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Extractor;

use App\Frontend\Domain\Extractor\TenantHostExtractor;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestStackTenantHostExtractor implements TenantHostExtractor
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function extract(): string
    {
        $fullHost = $this->requestStack->getCurrentRequest()->headers->get('host');

        $host = explode(":", $fullHost)[0] ?? '';

        if (empty($host)) {
            throw new \Exception("Tenant host is not set in the request headers.");
        }
        return $host;
    }
}
