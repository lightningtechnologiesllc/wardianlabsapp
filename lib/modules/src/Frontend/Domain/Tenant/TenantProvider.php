<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Tenant;

use App\Admin\Domain\Tenant\Tenant;

interface TenantProvider
{
    public function get(): Tenant;
}
