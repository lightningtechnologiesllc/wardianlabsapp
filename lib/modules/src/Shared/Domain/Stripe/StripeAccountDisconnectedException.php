<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

final class StripeAccountDisconnectedException extends \RuntimeException
{
    public function __construct(
        private readonly StripeAccountId $accountId,
        string $reason = 'invalid_grant',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'Stripe account "%s" is disconnected or OAuth token is invalid. Reason: %s. Please reconnect the account.',
                $accountId->value(),
                $reason
            ),
            0,
            $previous
        );
    }

    public function getAccountId(): StripeAccountId
    {
        return $this->accountId;
    }
}
