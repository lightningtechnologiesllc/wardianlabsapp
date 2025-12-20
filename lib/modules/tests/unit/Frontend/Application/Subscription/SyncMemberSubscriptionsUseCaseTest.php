<?php
declare(strict_types=1);

namespace Tests\Unit\App\Frontend\Application\Subscription;

use App\Frontend\Application\Subscription\SyncMemberSubscriptionsUseCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRole;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Member\GuildMembership;
use App\Frontend\Domain\Member\GuildMemberships;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Member\MemberId;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccounts;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\Tenant\InMemoryTenantPriceToRolesMappingRepository;
use Tests\Doubles\App\Frontend\Domain\DiscordId\DiscordIdMother;
use Tests\Doubles\App\Frontend\Infrastructure\Persistence\Repository\InMemoryMemberRepository;
use Tests\Doubles\App\Shared\Domain\Stripe\InMemoryStripeAccountRepository;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccountMother;
use Tests\Doubles\App\Shared\Domain\Tenant\TenantIdMother;

#[CoversClass(SyncMemberSubscriptionsUseCase::class)]
final class SyncMemberSubscriptionsUseCaseTest extends TestCase
{
    private TestHandler $logHandler;
    private Logger $logger;
    private InMemoryMemberRepository $memberRepository;
    private InMemoryStripeAccountRepository $stripeAccountRepository;
    private InMemoryTenantPriceToRolesMappingRepository $priceToRolesMappingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logger = new Logger("test", [$this->logHandler]);
        $this->memberRepository = new InMemoryMemberRepository();
        $this->stripeAccountRepository = new InMemoryStripeAccountRepository();
        $this->priceToRolesMappingRepository = new InMemoryTenantPriceToRolesMappingRepository();
    }

    public function testDoesNotChangeAnythingWhenAllSubscriptionsAreStillActive(): void
    {
        // Arrange: Create a member with active subscriptions
        $tenantId = TenantIdMother::create();
        $customerEmail = 'customer@example.com';
        $subscriptionId = 'sub_123';
        $priceId = 'price_abc';

        $guildId = new GuildId('1051640745611755640');
        $roleId = '1398028474089738423';

        $subscriptions = new StripeSubscriptions([
            new StripeSubscription($subscriptionId, $priceId, 'active')
        ]);

        $guildMembership = new GuildMembership(
            $guildId,
            new DiscordRoles([new DiscordRole(new DiscordRoleId($roleId))])
        );

        $member = Member::createLinked(
            $tenantId,
            new EmailAddress($customerEmail),
            $subscriptions,
            new GuildMemberships([$guildMembership]),
            DiscordIdMother::create()
        );

        $this->memberRepository->save($member);

        // Create Stripe account for the tenant
        $stripeAccount = StripeAccountMother::randomWith(tenantId: $tenantId);
        $this->stripeAccountRepository->save($stripeAccount);

        // Use InMemory provider that returns the same active subscriptions
        $stripeProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryStripeProvider([
            $customerEmail => new StripeSubscriptions([
                new StripeSubscription($subscriptionId, $priceId, 'active')
            ])
        ]);

        // Track Discord API calls - should be empty since subscriptions are still active
        $discordBotProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryDiscordBotProvider();

        // Act: Run the sync use case
        $useCase = new SyncMemberSubscriptionsUseCase(
            $this->memberRepository,
            $this->stripeAccountRepository,
            $stripeProvider,
            $discordBotProvider,
            $this->priceToRolesMappingRepository,
            $this->logger
        );

        ($useCase)();

        // Assert: No Discord role changes happened
        $this->assertEmpty($discordBotProvider->getRoleAdditions(), 'No roles should be added when subscriptions are still active');
    }

    public function testRemovesRolesWhenSubscriptionIsNoLongerActive(): void
    {
        // Arrange: Create a member with an active subscription and roles
        $tenantId = TenantIdMother::create();
        $customerEmail = 'customer@example.com';
        $subscriptionId = 'sub_123';
        $priceId = 'price_abc';

        $guildId = new GuildId('1051640745611755640');
        $roleId = '1398028474089738423';
        $discordUserId = DiscordIdMother::create();

        $subscriptions = new StripeSubscriptions([
            new StripeSubscription($subscriptionId, $priceId, 'active')
        ]);

        $guildMembership = new GuildMembership(
            $guildId,
            new DiscordRoles([new DiscordRole(new DiscordRoleId($roleId))])
        );

        $member = Member::createLinked(
            $tenantId,
            new EmailAddress($customerEmail),
            $subscriptions,
            new GuildMemberships([$guildMembership]),
            $discordUserId
        );

        $this->memberRepository->save($member);

        // Create Stripe account for the tenant
        $stripeAccount = StripeAccountMother::randomWith(tenantId: $tenantId);
        $this->stripeAccountRepository->save($stripeAccount);

        // Stripe now returns NO active subscriptions (subscription was cancelled)
        $stripeProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryStripeProvider([
            $customerEmail => new StripeSubscriptions([]) // Empty - no active subscriptions
        ]);

        // Track Discord API calls
        $discordBotProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryDiscordBotProvider();

        // Setup price-to-role mapping
        $mapping = new \App\Admin\Domain\Tenant\TenantPriceToRolesMapping(
            $tenantId,
            $guildId,
            [
                $priceId => [$roleId],
            ]
        );
        $this->priceToRolesMappingRepository->save($mapping);

        // Act: Run the sync use case
        $useCase = new SyncMemberSubscriptionsUseCase(
            $this->memberRepository,
            $this->stripeAccountRepository,
            $stripeProvider,
            $discordBotProvider,
            $this->priceToRolesMappingRepository,
            $this->logger
        );

        ($useCase)();

        // Assert: Roles should be removed since subscription is no longer active
        $this->assertNotEmpty($discordBotProvider->getRoleRemovals(), 'Roles should be removed when subscription is cancelled');
        $this->assertEquals($guildId->value(), $discordBotProvider->getRoleRemovals()[0]['guildId']);
        $this->assertEquals($discordUserId->value(), $discordBotProvider->getRoleRemovals()[0]['userId']);
        $this->assertEquals([$roleId], $discordBotProvider->getRoleRemovals()[0]['roleIds']);
    }

    public function testRemovesOnlyRolesForCancelledSubscriptionWhenMemberHasMultipleSubscriptions(): void
    {
        // Arrange: Create a member with 2 active subscriptions, each with different roles
        $tenantId = TenantIdMother::create();
        $customerEmail = 'customer@example.com';

        $subscription1Id = 'sub_123';
        $price1Id = 'price_abc';
        $role1Id = '1398028474089738423'; // Role for subscription 1

        $subscription2Id = 'sub_456';
        $price2Id = 'price_xyz';
        $role2Id = '1398028474089738999'; // Role for subscription 2

        $guildId = new GuildId('1051640745611755640');
        $discordUserId = DiscordIdMother::create();

        // Member starts with 2 active subscriptions
        $subscriptions = new StripeSubscriptions([
            new StripeSubscription($subscription1Id, $price1Id, 'active'),
            new StripeSubscription($subscription2Id, $price2Id, 'active')
        ]);

        // Member has roles for both subscriptions
        $guildMembership = new GuildMembership(
            $guildId,
            new DiscordRoles([
                new DiscordRole(new DiscordRoleId($role1Id)),
                new DiscordRole(new DiscordRoleId($role2Id))
            ])
        );

        $member = Member::createLinked(
            $tenantId,
            new EmailAddress($customerEmail),
            $subscriptions,
            new GuildMemberships([$guildMembership]),
            $discordUserId
        );

        $this->memberRepository->save($member);

        // Create Stripe account for the tenant
        $stripeAccount = StripeAccountMother::randomWith(tenantId: $tenantId);
        $this->stripeAccountRepository->save($stripeAccount);

        // Stripe now returns only subscription 2 (subscription 1 was cancelled)
        $stripeProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryStripeProvider([
            $customerEmail => new StripeSubscriptions([
                new StripeSubscription($subscription2Id, $price2Id, 'active')
            ])
        ]);

        // Track Discord API calls
        $discordBotProvider = new \Tests\Doubles\App\Frontend\Infrastructure\Provider\InMemoryDiscordBotProvider();

        // Setup price-to-role mapping so we know which roles belong to which subscription
        $mapping = new \App\Admin\Domain\Tenant\TenantPriceToRolesMapping(
            $tenantId,
            $guildId,
            [
                $price1Id => [$role1Id],
                $price2Id => [$role2Id],
            ]
        );
        $this->priceToRolesMappingRepository->save($mapping);

        // Act: Run the sync use case
        $useCase = new SyncMemberSubscriptionsUseCase(
            $this->memberRepository,
            $this->stripeAccountRepository,
            $stripeProvider,
            $discordBotProvider,
            $this->priceToRolesMappingRepository,
            $this->logger
        );

        ($useCase)();

        // Assert: Should only remove role1 (for cancelled subscription 1), not role2
        $this->assertNotEmpty($discordBotProvider->getRoleRemovals(), 'Should remove roles for cancelled subscription');
        $this->assertEquals([$role1Id], $discordBotProvider->getRoleRemovals()[0]['roleIds'], 'Should only remove role for cancelled subscription');
        $this->assertEmpty($discordBotProvider->getRoleAdditions(), 'Should not add any roles');

        // Verify member's subscriptions were updated
        $updatedMember = $this->memberRepository->findByMemberId($member->getId());
        $this->assertEquals(1, $updatedMember->getSubscriptions()->count());
        $this->assertEquals($subscription2Id, $updatedMember->getSubscriptions()->first()->getId());
    }
}
