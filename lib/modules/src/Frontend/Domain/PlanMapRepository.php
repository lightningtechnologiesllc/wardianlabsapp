<?php
declare(strict_types=1);

namespace App\Frontend\Domain;

use Symfony\Component\Uid\Uuid;

interface PlanMapRepository
{
    public function findByTenantId(Uuid $tenantId): ?PlanMap;
}
