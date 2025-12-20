<?php
declare(strict_types=1);

namespace Tests\Integration\App\Frontend\Ui\Command;

use App\Frontend\Ui\Command\SyncMemberSubscriptionsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\Tenant\InMemoryTenantPriceToRolesMappingRepository;
use Tests\Doubles\App\Frontend\Infrastructure\Persistence\Repository\InMemoryMemberRepository;
use Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryDiscordBotProvider;
use Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryStripeProvider;
use Tests\Doubles\App\Shared\Domain\Stripe\InMemoryStripeAccountRepository;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use App\Frontend\Application\Subscription\SyncMemberSubscriptionsUseCase;

#[CoversClass(SyncMemberSubscriptionsCommand::class)]
final class SyncMemberSubscriptionsCommandTest extends TestCase
{
    private TestHandler $logHandler;
    private Logger $logger;
    private InMemoryMemberRepository $memberRepository;
    private InMemoryStripeAccountRepository $stripeAccountRepository;
    private InMemoryStripeProvider $stripeProvider;
    private InMemoryDiscordBotProvider $discordBotProvider;
    private InMemoryTenantPriceToRolesMappingRepository $priceToRolesMappingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logger = new Logger("test", [$this->logHandler]);
        $this->memberRepository = new InMemoryMemberRepository();
        $this->stripeAccountRepository = new InMemoryStripeAccountRepository();
        $this->stripeProvider = new InMemoryStripeProvider([]);
        $this->discordBotProvider = new InMemoryDiscordBotProvider();
        $this->priceToRolesMappingRepository = new InMemoryTenantPriceToRolesMappingRepository();
    }

    public function testCommandExecutesSuccessfully(): void
    {
        // Arrange
        $useCase = new SyncMemberSubscriptionsUseCase(
            $this->memberRepository,
            $this->stripeAccountRepository,
            $this->stripeProvider,
            $this->discordBotProvider,
            $this->priceToRolesMappingRepository,
            $this->logger
        );

        $command = new SyncMemberSubscriptionsCommand($useCase);
        $commandTester = new CommandTester($command);

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertEquals(0, $exitCode, 'Command should exit successfully');
        $this->assertStringContainsString('Starting subscription sync', $commandTester->getDisplay());
        $this->assertStringContainsString('Subscription sync completed', $commandTester->getDisplay());
    }
}
