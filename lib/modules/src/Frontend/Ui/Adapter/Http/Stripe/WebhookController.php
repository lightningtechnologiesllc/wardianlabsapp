<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Adapter\Http\Stripe;

use App\Frontend\Application\Stripe\Webhook\HandleSubscriptionCreatedUseCase;
use App\Frontend\Application\Stripe\Webhook\HandleSubscriptionDeletedUseCase;
use App\Frontend\Domain\Stripe\SubscriptionCreatedEvent;
use App\Frontend\Domain\Stripe\SubscriptionDeletedEvent;
use App\Shared\Infrastructure\Stripe\StripeWebhookValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stripe/webhook', name: 'frontend_stripe_webhook', methods: ['POST'])]
final readonly class WebhookController
{
    public function __construct(
        private StripeWebhookValidator $webhookValidator,
        private HandleSubscriptionCreatedUseCase $handleSubscriptionCreated,
        private HandleSubscriptionDeletedUseCase $handleSubscriptionDeleted,
        private LoggerInterface $logger,
    )
    {
    }

    public function __invoke(Request $request): Response
    {
        // Log the full request for debugging
        $this->logger->info('Stripe webhook received', ["request" => $request->toArray()]);

        try {
            // Validate webhook signature and construct event
            $event = $this->webhookValidator->validateAndConstructEvent($request);

            $this->logger->info('Stripe webhook validated', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            // Handle different event types
            match ($event->type) {
                'customer.subscription.created' => ($this->handleSubscriptionCreated)(
                    SubscriptionCreatedEvent::fromStripeEvent($event)
                ),
                'customer.subscription.deleted' => ($this->handleSubscriptionDeleted)(
                    SubscriptionDeletedEvent::fromStripeEvent($event)
                ),
                default => $this->logger->info('Unhandled Stripe webhook event type', [
                    'event_type' => $event->type,
                    'event_id' => $event->id,
                ])
            };

            return new Response('ok', 200);
        } catch (\RuntimeException $e) {
            $this->logger->error('Stripe webhook validation failed', [
                'error' => $e->getMessage(),
            ]);

            return new Response('Invalid signature', 400);
        } catch (\Exception $e) {
            $this->logger->error('Error processing Stripe webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new Response('Internal error', 500);
        }
    }
}
