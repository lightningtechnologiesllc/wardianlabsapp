<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\Stripe\UseCase;

use App\Admin\Application\Stripe\UseCase\AssignPricesToRolesUseCase;
use App\Admin\Application\Stripe\UseCase\DiscordRoleDoesNotBelongToGuildException;
use App\Admin\Application\Stripe\UseCase\PriceDoesNotBelongToStripeAccountException;
use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Infrastructure\Provider\Stripe\StripePrices;
use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Infrastructure\Provider\ApiDiscordBotManagerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\Tenant\TenantMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\Tenant\InMemoryTenantPriceToRolesMappingRepository;
use Tests\Doubles\App\Admin\Infrastructure\Provider\Stripe\InMemoryAccountStripeProvider;
use Tests\Doubles\App\Admin\Infrastructure\Provider\Stripe\StripePriceMother;
use Tests\Doubles\App\Shared\Domain\Stripe\InMemoryStripeAccountRepository;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccountMother;

#[CoversClass(AssignPricesToRolesUseCase::class)]
final class AssignPricesToRolesUseCaseTest extends TestCase
{
    private AssignPricesToRolesUseCase $useCase;
    private GuildId $guildId;
    private DiscordRoleId $predefinedRoleId;
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountStripeProvider = new InMemoryAccountStripeProvider();
        $this->apiDiscordBotManagerProvider = $this->createMock(ApiDiscordBotManagerProvider::class);
        $this->tenantPriceToRolesMappingRepository = new InMemoryTenantPriceToRolesMappingRepository();

        $this->predefinedRoleId = DiscordRoleId::random();
        $this->guildId = GuildId::random();
        $this->tenant = TenantMother::random();

        $this->apiDiscordBotManagerProvider
            ->expects($this->once())
            ->method('getGuildData')
            ->with($this->guildId)
            ->willReturn([
                'roles' => [
                    [
                        "id" => $this->predefinedRoleId->value(),
                        "name" => "MyRole",
                        "description" => null,
                        "permissions" => "2415921152",
                        "position" => 1,
                        "hoist" => false,
                        "managed" => true,
                        "mentionable" => false,
                        "icon" => null,
                        "unicode_emoji" => null,
                        "tags" => [
                            "bot_id" => "1400919406074138685"
                        ],
                    ]
                ],
            ]);
        $this->accountRepository = new InMemoryStripeAccountRepository();
        $this->useCase = new AssignPricesToRolesUseCase(
            $this->accountStripeProvider,
            $this->apiDiscordBotManagerProvider,
            $this->tenantPriceToRolesMappingRepository,
        );
    }

    public function testThrowErrorIfAPriceDoesNotBelongToStripeAccount(): void
    {
        $account = StripeAccountMother::random();
        $pricesToRolesMapping = [
            'price_invalid' => [DiscordId::random()->value()],
        ];
        $stripePrice = StripePriceMother::fixed();
        $this->accountStripeProvider->addPricesForAccount($account, new StripePrices(items: [
            $stripePrice
        ]));

        $this->expectExceptionObject(new PriceDoesNotBelongToStripeAccountException('price_invalid', $account->getAccountId()));

        ($this->useCase)($this->tenant, $account, $this->guildId, $pricesToRolesMapping);
    }

    public function testThrowErrorIfADiscordRoleDoesNotBelongToGuild(): void
    {
        $account = StripeAccountMother::random();
        $stripePrice = StripePriceMother::fixed();
        $roleId = DiscordRoleId::random();
        $pricesToRolesMapping = [
            $stripePrice->getId() => [$roleId->value()],
        ];
        $this->accountStripeProvider->addPricesForAccount($account, new StripePrices(items: [
            $stripePrice
        ]));

        $this->expectExceptionObject(new DiscordRoleDoesNotBelongToGuildException($roleId, $this->guildId));

        ($this->useCase)($this->tenant, $account, $this->guildId, $pricesToRolesMapping);
    }


    public function testAssignPricesToRolesSuccessfully(): void
    {
        $account = StripeAccountMother::random();
        $stripePrice = StripePriceMother::fixed();
        $pricesToRolesMapping = [
            $stripePrice->getId() => [
                $this->predefinedRoleId->value()],
        ];
        $this->accountStripeProvider->addPricesForAccount($account, new StripePrices(items: [
            $stripePrice
        ]));

        ($this->useCase)($this->tenant, $account, $this->guildId, $pricesToRolesMapping);
    }

    public function testDoesNotSaveMappingsIfPricesToRolesMappingIsEmpty(): void
    {
        $this->markTestIncomplete('This test case is not implemented yet.');
    }

    public function testAssignMappingToExistingTenantAndGuildOverwritesPreviousMappings(): void
    {
        $this->markTestIncomplete('This test case is not implemented yet.');
    }
}
