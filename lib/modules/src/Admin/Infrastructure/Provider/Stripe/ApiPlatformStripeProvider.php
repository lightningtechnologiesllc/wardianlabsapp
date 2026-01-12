<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

use App\Admin\Domain\Stripe\CustomerNotFoundException;
use App\Admin\Domain\Stripe\PlatformStripeProvider;
use Psr\Log\LoggerInterface;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

final readonly class ApiPlatformStripeProvider implements PlatformStripeProvider
{
    public function __construct(
        private StripeClient $stripeClient,
        private LoggerInterface $logger,
    ) {
    }

    public function getCustomerEmail(string $customerId): string
    {
        try {
            $customer = $this->stripeClient->customers->retrieve($customerId);
            return $customer->email;
        } catch (InvalidRequestException $e) {
            $this->logger->error('Customer not found in platform Stripe', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw new CustomerNotFoundException($customerId);
        }
    }
}
