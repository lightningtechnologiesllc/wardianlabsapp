<?php
declare(strict_types=1);

namespace Tests\Integration\App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Ui\Adapter\Http\Stripe\PlatformWebhookController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(PlatformWebhookController::class)]
final class PlatformWebhookControllerTest extends WebTestCase
{
    public function testControllerIsRegistered(): void
    {
        // Verify the controller is properly registered in the container
        self::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has(PlatformWebhookController::class));
    }

    public function testWebhookEndpointExists(): void
    {
        $client = static::createClient();

        // Attempt to access the webhook endpoint
        // It should return 400 due to missing signature, not 404
        $client->request('POST', '/admin/stripe/platform-webhook', [], [], [], '{}');

        // Should not be 404 (not found)
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testReturns400WhenSignatureInvalid(): void
    {
        $this->markTestIncomplete('Webhook signature validation to be implemented');
    }

    public function testReturns200EvenWhenEventIsUnknown(): void
    {
        $this->markTestIncomplete('Unknown event handling to be implemented');
    }
}
