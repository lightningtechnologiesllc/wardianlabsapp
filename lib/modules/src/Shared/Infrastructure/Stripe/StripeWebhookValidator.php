<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Stripe;

use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;

final readonly class StripeWebhookValidator
{
    public function __construct(
        private string $webhookSecret,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * Validates the Stripe webhook signature and returns the event
     * For Stripe Connect: Use webhook endpoint secret from your platform account
     *
     * @throws \RuntimeException if signature validation fails
     */
    public function validateAndConstructEvent(Request $request): \Stripe\Event
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (!$sigHeader) {
            $this->logger->error('Stripe webhook missing signature header');
            throw new \RuntimeException('Missing Stripe signature header');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );

            $this->logger->info('Stripe webhook signature validated', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            return $event;
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Invalid webhook signature: ' . $e->getMessage());
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe webhook payload invalid', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Invalid webhook payload: ' . $e->getMessage());
        }
    }
}
