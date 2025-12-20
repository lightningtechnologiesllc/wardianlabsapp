<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Shared\Domain\Stripe;

use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Tenant\TenantId;
use Tests\Doubles\App\Shared\Domain\Tenant\TenantIdMother;

final class StripeAccountMother
{
    public static function random(): StripeAccount
    {
        return new StripeAccount(
            accountId: StripeAccountId::random(),
            tenantId: TenantIdMother::create(),
            stripeProviderAccountId: 'acct_' . bin2hex(random_bytes(16)),
            displayName: 'Test Account',
            accessToken: StripeAccessTokenMother::random(),
        );
    }
    public static function randomWith(
        ?StripeAccountId $accountId = null,
        ?TenantId $tenantId = null,
        ?string $stripeProviderAccountId = null,
    ): StripeAccount
    {
        return new StripeAccount(
            accountId: $accountId ?? StripeAccountId::random(),
            tenantId: $tenantId ?? TenantIdMother::create(),
            stripeProviderAccountId: $stripeProviderAccountId ?? 'acct_' . bin2hex(random_bytes(16)),
            displayName: 'Test Account',
            accessToken: StripeAccessTokenMother::random(),
        );
    }
}
