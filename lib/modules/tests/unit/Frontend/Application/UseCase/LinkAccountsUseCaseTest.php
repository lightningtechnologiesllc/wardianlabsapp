<?php
declare(strict_types=1);

namespace Tests\Unit\App\Frontend\Application\UseCase;

use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
use App\Frontend\Application\UseCase\LinkAccountsUseCase;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Providers\DiscordUserManagerProvider;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\Tenant\TenantMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryTenantRepository;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\Tenant\InMemoryTenantPriceToRolesMappingRepository;
use Tests\Doubles\App\Frontend\Infrastructure\Persistence\Repository\InMemoryMemberRepository;
use Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryStripeProvider;

#[CoversClass(LinkAccountsUseCase::class)]
final class LinkAccountsUseCaseTest extends TestCase
{
    private $planId;

    public function setUp(): void
    {
        parent::setUp();

        $email = 'vicent@techabreath.com';

        $this->logHandler = new TestHandler();
        $this->logger = new Logger("test", [$this->logHandler]);
        $this->tenantPriceToRolesMappingRepository = new InMemoryTenantPriceToRolesMappingRepository();
        $this->planId = 'price_1RoVMePOQ7ui3NRxAQv5Jtpc';
        $this->stripeProvider = new InMemoryStripeProvider([$email => new StripeSubscriptions([new StripeSubscription('id', $this->planId, 'active')])]);
        $this->discordUserManagerProvider = $this->createMock(DiscordUserManagerProvider::class);
        $this->tenantRepository = new InMemoryTenantRepository();
        $this->tenant = TenantMother::random();
        $this->tenantRepository->save($this->tenant);
        $this->memberRepository = new InMemoryMemberRepository();
    }

    public function testDoesNotAssignRoleWhenNoValidSubscription(): void
    {
        $useCase = new LinkAccountsUseCase(
            $this->logger,
            $this->tenantPriceToRolesMappingRepository,
            new InMemoryStripeProvider([]),
            $this->discordUserManagerProvider,
            $this->memberRepository,
        );
        $this->discordUserManagerProvider->expects($this->never())
            ->method('addRolesToUser');

        $result = ($useCase)($this->tenant->getId(), "invalid@techabreath.com", "123456789");

        $this->assertStringContainsString(
            'No valid subscriptions found for user',
            $this->logHandler->getRecords()[0]['message']
        );
        $this->assertEmpty($result);
    }

    public function testDoesNotAssignRoleWhenTenantHasNoMappings(): void
    {
        $email = "vicent@techabreath.com";
        $this->stripeProvider = new InMemoryStripeProvider([$email => new StripeSubscriptions([new StripeSubscription('id', 'unknown_plan_id', 'active')])]);

        $useCase = new LinkAccountsUseCase(
            $this->logger,
            $this->tenantPriceToRolesMappingRepository,
            $this->stripeProvider,
            $this->discordUserManagerProvider,
            $this->memberRepository,
        );
        $this->discordUserManagerProvider->expects($this->never())
            ->method('addRolesToUser');

        $result = ($useCase)($this->tenant->getId(), $email, "123456789");

        $this->assertStringContainsString(
            'Tenant has no Price to Roles Mapping configured',
            $this->logHandler->getRecords()[0]['message']
        );
        $this->assertEmpty($result);

    }

    public function testDoesNotAssignRoleWhenItHasNoValidPlan(): void
    {
        $email = "vicent@techabreath.com";
        $this->stripeProvider = new InMemoryStripeProvider([$email => new StripeSubscriptions([new StripeSubscription('id', 'unknown_plan_id', 'active')])]);
        $this->tenantPriceToRolesMappingRepository->save(new TenantPriceToRolesMapping(
            $this->tenant->getId(),
            GuildId::random(),
            [
                'price_random' => ['1398028474089738423']
            ]
        ));

        $useCase = new LinkAccountsUseCase(
            $this->logger,
            $this->tenantPriceToRolesMappingRepository,
            $this->stripeProvider,
            $this->discordUserManagerProvider,
            $this->memberRepository,
        );
        $this->discordUserManagerProvider->expects($this->never())
            ->method('addRolesToUser');

        $result = ($useCase)($this->tenant->getId(), $email, "123456789");

        $this->assertStringContainsString(
            'No roles to assign for user',
            $this->logHandler->getRecords()[0]['message']
        );
        $this->assertEmpty($result);
    }

    public function testAssignRole(): void
    {
        $email = "vicent@techabreath.com";
        $useCase = new LinkAccountsUseCase(
            $this->logger,
            $this->tenantPriceToRolesMappingRepository,
            $this->stripeProvider,
            $this->discordUserManagerProvider,
            $this->memberRepository,
        );
        $this->stripeProvider = new InMemoryStripeProvider([$email => new StripeSubscriptions([new StripeSubscription('id', $this->planId, 'active')])]);
        $guildId = GuildId::random();
        $this->tenantPriceToRolesMappingRepository->save(new TenantPriceToRolesMapping(
            $this->tenant->getId(),
            $guildId,
            [
                $this->planId => ['1398028474089738423']
            ]
        ));
        $this->discordUserManagerProvider->expects($this->once())
            ->method('addRolesToUser')
            ->with(
                $guildId->value(),
                '123456789',
                ['1398028474089738423']
            )
        ;

        ($useCase)($this->tenant->getId(), "vicent@techabreath.com", "123456789");
    }

}
