<?php
declare(strict_types=1);

namespace App\Admin\Application\Tenant;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\TenantRepository;

final readonly class UpdateTenantConfigUseCase
{
    public function __construct(
        private TenantRepository $tenantRepository,
    ) {
    }

    public function __invoke(
        Tenant $tenant,
        string $name,
        string $subdomain,
        string $emailDSN,
        string $emailFromAddress
    ): void {
        $tenant->updateName($name);
        $tenant->updateSubdomain($subdomain);
        $tenant->updateEmailDSN($emailDSN);
        $tenant->updateEmailFromAddress($emailFromAddress);

        $this->tenantRepository->save($tenant);
    }
}
