<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\HandlePlatformSubscriptionWebhookUseCase;
use App\Shared\Infrastructure\Stripe\StripeWebhookValidator;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stripe/platform-webhook', name: 'admin_platform_stripe_webhook', methods: ['POST'])]
final readonly class PlatformWebhookController
{
    public function __construct(
        private StripeWebhookValidator                   $webhookValidator,
        private HandlePlatformSubscriptionWebhookUseCase $handleWebhookUseCase,
        private LoggerInterface                          $logger,
    )
    {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->webhookValidator->validateAndConstructEvent($request);
            $eventArray = json_decode(json_encode($event), true);

            $supportedEvents = [
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
            ];

            if (!in_array($event->type, $supportedEvents, true)) {
                return new Response('ok', 200);
            }

            $this->logger->info('Platform Stripe webhook received', ['body' => json_decode($request->getContent(), true)]);
            $this->logger->info('Platform Stripe webhook validated', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            ($this->handleWebhookUseCase)($eventArray);

            return new Response('ok', 200);
        } catch (\RuntimeException $e) {
            $this->logger->error('Platform webhook validation failed', [
                'error' => $e->getMessage(),
            ]);

            return new Response('Invalid signature', 400);
        } catch (\Exception $e) {
            $this->logger->error('Error processing platform webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event' => $eventArray,
            ]);

            return new Response('Internal error', 500);
        }
    }
}
