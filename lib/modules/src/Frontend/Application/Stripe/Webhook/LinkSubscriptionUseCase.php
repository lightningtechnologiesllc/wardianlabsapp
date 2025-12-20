<?php
declare(strict_types=1);

namespace App\Frontend\Application\Stripe\Webhook;

use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRole;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Member\GuildMembership;
use App\Frontend\Domain\Member\GuildMemberships;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Member\MemberRepository;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Frontend\Infrastructure\Provider\ApiDiscordBotManagerProvider;
use App\Frontend\Infrastructure\Provider\HttpStripeProvider;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Stripe\AccountLinkingToken;
use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Psr\Log\LoggerInterface;

final readonly class LinkSubscriptionUseCase
{
    public function __construct(
        private AccountLinkingTokenRepository $linkingTokenRepository,
        private TenantPriceToRolesMappingRepository $priceToRolesMappingRepository,
        private MemberRepository $memberRepository,
        private ApiDiscordBotManagerProvider $discordBotProvider,
        private StripeAccountRepository $stripeAccountRepository,
        private HttpStripeProvider $stripeProvider,
        private LoggerInterface $logger,
    )
    {
    }

    public function __invoke(AccountLinkingToken $linkingToken, string $discordUserId, string $discordAccessToken): void
    {
        $tenantId = $linkingToken->getTenantId();
        $customerEmail = $linkingToken->getCustomerEmail();

        // Get Stripe account for this tenant
        $stripeAccounts = $this->stripeAccountRepository->findByTenantId($tenantId);

        if ($stripeAccounts->isEmpty()) {
            $this->logger->error('No Stripe account found for tenant', [
                'tenant_id' => $tenantId->value(),
            ]);
            throw new \RuntimeException('No Stripe account configured for this tenant');
        }

        $stripeAccount = $stripeAccounts->first();

        // Fetch ALL active subscriptions for this customer
        $allSubscriptions = $this->fetchActiveSubscriptionsForCustomer($stripeAccount, $customerEmail);

        if ($allSubscriptions->isEmpty()) {
            $this->logger->warning('No active subscriptions found for customer', [
                'customer_email' => $customerEmail,
                'tenant_id' => $tenantId->value(),
            ]);
            throw new \RuntimeException('No active subscriptions found');
        }

        // Get all price to roles mappings for this tenant
        $priceToRolesMappings = $this->priceToRolesMappingRepository->findByTenant($tenantId);

        if ($priceToRolesMappings->isEmpty()) {
            $this->logger->error('No price to roles mapping found for tenant', [
                'tenant_id' => $tenantId->value(),
            ]);
            throw new \RuntimeException('Tenant configuration not found');
        }

        // Collect all roles to assign across all guilds
        $rolesToAssignPerGuild = [];

        foreach ($priceToRolesMappings->getIterator() as $mapping) {
            foreach ($allSubscriptions as $subscription) {
                $roleIds = $mapping->getRolesPerPrice($subscription->getPlanId());

                if (!empty($roleIds)) {
                    $guildId = $mapping->getGuildId()->value();
                    if (!isset($rolesToAssignPerGuild[$guildId])) {
                        $rolesToAssignPerGuild[$guildId] = [];
                    }
                    $rolesToAssignPerGuild[$guildId] = array_unique(array_merge(
                        $rolesToAssignPerGuild[$guildId],
                        $roleIds
                    ));
                }
            }
        }

        if (empty($rolesToAssignPerGuild)) {
            $this->logger->warning('No roles to assign for any subscriptions', [
                'customer_email' => $customerEmail,
                'tenant_id' => $tenantId->value(),
            ]);
        }

        // Create guild memberships with roles for all guilds
        $guildMemberships = [];
        foreach ($rolesToAssignPerGuild as $guildId => $roleIds) {
            $roles = array_map(
                fn($roleId) => new DiscordRole(new DiscordRoleId($roleId)),
                $roleIds
            );

            $guildMemberships[] = new GuildMembership(
                new GuildId((string)$guildId),
                new DiscordRoles($roles)
            );
        }

        // Check if member already exists by Discord ID or email
        $existingMember = $this->memberRepository->findByDiscordId(new DiscordId($discordUserId));

        if (!$existingMember) {
            // Also check by email in case member was created as pending
            $existingMember = $this->memberRepository->findByCustomerEmail($customerEmail);
        }

        if ($existingMember) {
            // Update existing member's subscriptions and guild memberships
            $existingMember->updateSubscriptions($allSubscriptions);
            $existingMember->updateGuildMemberships(new GuildMemberships($guildMemberships));

            // If member was pending, link to Discord now
            if ($existingMember->isPending()) {
                $existingMember->linkToDiscord(new DiscordId($discordUserId));
            }

            $this->memberRepository->save($existingMember);

            $this->logger->info('Updated existing member with all subscriptions', [
                'member_id' => $existingMember->getId()->value(),
                'discord_user_id' => $discordUserId,
                'subscription_count' => $allSubscriptions->count(),
            ]);
        } else {
            // Create member already linked to Discord
            $member = Member::createLinked(
                $tenantId,
                new EmailAddress($customerEmail),
                $allSubscriptions,
                new GuildMemberships($guildMemberships),
                new DiscordId($discordUserId)
            );

            $this->memberRepository->save($member);

            $this->logger->info('Created new member with all subscriptions', [
                'tenant_id' => $tenantId->value(),
                'discord_user_id' => $discordUserId,
                'subscription_count' => $allSubscriptions->count(),
            ]);
        }

        // Add user to guilds and assign Discord roles
        foreach ($rolesToAssignPerGuild as $guildId => $roleIds) {
            try {
                // First, add user to the guild (requires guilds.join scope)
                $wasAdded = $this->discordBotProvider->addUserToGuild(
                    (string)$guildId,
                    $discordUserId,
                    $discordAccessToken
                );

                $this->logger->info($wasAdded ? 'Added user to guild' : 'User already in guild', [
                    'guild_id' => $guildId,
                    'discord_user_id' => $discordUserId,
                ]);

                // Then assign roles
                $this->discordBotProvider->addRolesToUser(
                    (string)$guildId,
                    $discordUserId,
                    $roleIds
                );

                $this->logger->info('Assigned Discord roles', [
                    'guild_id' => $guildId,
                    'discord_user_id' => $discordUserId,
                    'role_ids' => $roleIds,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to add user to guild or assign roles', [
                    'guild_id' => $guildId,
                    'discord_user_id' => $discordUserId,
                    'role_ids' => $roleIds,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we still want to mark the token as linked
            }
        }

        // Mark the linking token as linked
        $linkedToken = $linkingToken->linkToDiscordUser($discordUserId);
        $this->linkingTokenRepository->save($linkedToken);

        $this->logger->info('All subscriptions linked to Discord user', [
            'discord_user_id' => $discordUserId,
            'tenant_id' => $tenantId->value(),
            'subscription_count' => $allSubscriptions->count(),
        ]);
    }

    private function fetchActiveSubscriptionsForCustomer(
        \App\Shared\Domain\Stripe\StripeAccount $stripeAccount,
        string $customerEmail
    ): StripeSubscriptions {
        // Use HttpStripeProvider to fetch customers with subscriptions
        $subscriptionsData = $this->stripeProvider->fetchCollection(
            $stripeAccount,
            fn(\Stripe\StripeClient $client) => $client->customers->search([
                'query' => "email:'{$customerEmail}'",
                'expand' => ['data.subscriptions'],
            ])
        );

        $activeSubscriptions = [];

        foreach ($subscriptionsData->data as $customer) {
            if (isset($customer->subscriptions)) {
                foreach ($customer->subscriptions->data as $subscription) {
                    if (in_array($subscription->status, ['active', 'trialing'])) {
                        $priceId = $subscription->items->data[0]->price->id ?? null;
                        if ($priceId) {
                            $activeSubscriptions[] = new StripeSubscription(
                                $subscription->id,
                                $priceId,
                                $subscription->status
                            );
                        }
                    }
                }
            }
        }

        return new StripeSubscriptions($activeSubscriptions);
    }
}
