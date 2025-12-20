<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Extractor;

interface TenantHostExtractor
{
    public function extract(): string;
}
