<?php
declare(strict_types=1);

namespace Tests\Integration\App\Frontend\Infrastructure\Provider;

use App\Frontend\Infrastructure\Provider\HttpStripeProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(HttpStripeProvider::class)]
final class StripeProviderTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setRequestMock('wardianlabs.test');
    }

    public function testHasValidSubscription(): void
    {
        $this->markTestIncomplete("");
        $email = 'vicent@techabreath.com';

        $hasValidSubscription = $this->getProvider()->hasValidSubscription($email);

        $this->assertTrue($hasValidSubscription);
    }

    public function testItHasNoValidSubscription(): void
    {
        $this->markTestIncomplete("");
        $email = 'invalid@gmail.com';

        $hasValidSubscription = $this->getProvider()->hasValidSubscription($email);

        $this->assertFalse($hasValidSubscription);
    }

    public function testPlanIsTheExpected(): void
    {
        $this->markTestIncomplete("");
        $email = 'vicent@techabreath.com';

        $validSubscriptions = $this->getProvider()->getValidSubscriptionsForUser($email);

        $this->assertNotEmpty($validSubscriptions);
        $subscription = $validSubscriptions->first();
        $this->assertEquals($subscription->getPlanId(), 'price_1RoVMePOQ7ui3NRxAQv5Jtpc');
    }

    public function setRequestMock(string $host): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([], [], [], [], [], [
                'HTTP_HOST' => $host,
                'HTTP_USER_AGENT' => 'PHPUnit',
            ]));

        $this->getContainer()->set(RequestStack::class, $requestStack);
    }

    private function getProvider(): HttpStripeProvider
    {
        return $this->service(HttpStripeProvider::class);
    }

}
