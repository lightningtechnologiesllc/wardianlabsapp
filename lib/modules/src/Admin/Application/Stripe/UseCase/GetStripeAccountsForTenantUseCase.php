<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Admin\Domain\Tenant\Tenant;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Stripe\StripeAccounts;

final readonly class GetStripeAccountsForTenantUseCase
{
    public function __construct(
        private StripeAccountRepository $stripeAccountRepository,
    )
    {}

    public function __invoke(Tenant $tenant): StripeAccounts
    {
        return $this->stripeAccountRepository->findByTenantId($tenant->getId());
    }
}
