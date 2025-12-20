<?php
declare(strict_types=1);

namespace App\Frontend\Application\Subscription;

use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Frontend\Domain\Member\MemberRepository;
use App\Frontend\Domain\Providers\DiscordUserManagerProvider;
use App\Frontend\Domain\Stripe\StripeProvider;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Psr\Log\LoggerInterface;

final readonly class SyncMemberSubscriptionsUseCase
{
    public function __construct(
        private MemberRepository $memberRepository,
        private StripeAccountRepository $stripeAccountRepository,
        private StripeProvider $stripeProvider,
        private DiscordUserManagerProvider $discordBotProvider,
        private TenantPriceToRolesMappingRepository $priceToRolesMappingRepository,
        private LoggerInterface $logger,
    )
    {
    }

    public function __invoke(): void
    {
        $this->logger->info('Starting subscription sync for all members');

        $members = $this->memberRepository->findAll();

        $this->logger->info('Found members to sync', ['count' => count($members)]);

        foreach ($members as $member) {
            $this->syncMemberSubscriptions($member);
        }

        $this->logger->info('Subscription sync completed');
    }

    private function syncMemberSubscriptions(\App\Frontend\Domain\Member\Member $member): void
    {
        // Skip members that are not linked to Discord
        if (!$member->isLinked()) {
            return;
        }

        $this->logger->info('Syncing subscriptions for member', [
            'member_id' => $member->getId()->value(),
            'customer_email' => $member->getCustomerEmail()->value(),
        ]);

        // Fetch current active subscriptions from Stripe
        $currentSubscriptions = $this->stripeProvider->getValidSubscriptionsForUser(
            $member->getCustomerEmail()->value(),
            $member->getTenantId()
        );

        $storedSubscriptions = $member->getSubscriptions();

        // Check if subscriptions have changed
        if ($this->subscriptionsAreEqual($storedSubscriptions, $currentSubscriptions)) {
            $this->logger->debug('Subscriptions unchanged for member', [
                'member_id' => $member->getId()->value(),
            ]);
            return;
        }

        $this->logger->info('Subscriptions changed for member', [
            'member_id' => $member->getId()->value(),
            'stored_count' => $storedSubscriptions->count(),
            'current_count' => $currentSubscriptions->count(),
        ]);

        // Find cancelled subscriptions (in stored but not in current)
        $cancelledSubscriptions = $this->findCancelledSubscriptions($storedSubscriptions, $currentSubscriptions);

        if (!empty($cancelledSubscriptions)) {
            $this->removeRolesForCancelledSubscriptions($member, $cancelledSubscriptions);
        }

        // Update member's subscriptions
        $member->updateSubscriptions($currentSubscriptions);
        $this->memberRepository->save($member);
    }

    /**
     * @return \App\Frontend\Domain\Stripe\StripeSubscription[]
     */
    private function findCancelledSubscriptions(
        \App\Frontend\Domain\Stripe\StripeSubscriptions $stored,
        \App\Frontend\Domain\Stripe\StripeSubscriptions $current
    ): array {
        $currentIds = array_map(fn($sub) => $sub->getId(), iterator_to_array($current));
        $cancelled = [];

        foreach ($stored as $storedSub) {
            if (!in_array($storedSub->getId(), $currentIds)) {
                $cancelled[] = $storedSub;
            }
        }

        return $cancelled;
    }

    private function removeRolesForCancelledSubscriptions(
        \App\Frontend\Domain\Member\Member $member,
        array $cancelledSubscriptions
    ): void {
        // Get price-to-role mappings for this tenant
        $mappings = $this->priceToRolesMappingRepository->findByTenant($member->getTenantId());

        if ($mappings->isEmpty()) {
            $this->logger->warning('No price-to-role mappings found for tenant', [
                'tenant_id' => $member->getTenantId()->value(),
            ]);
            return;
        }

        // For each cancelled subscription, find which roles to remove
        $rolesToRemovePerGuild = [];

        foreach ($cancelledSubscriptions as $cancelledSub) {
            foreach ($mappings->getIterator() as $mapping) {
                $roleIds = $mapping->getRolesPerPrice($cancelledSub->getPlanId());

                if (!empty($roleIds)) {
                    $guildId = $mapping->getGuildId()->value();
                    if (!isset($rolesToRemovePerGuild[$guildId])) {
                        $rolesToRemovePerGuild[$guildId] = [];
                    }
                    $rolesToRemovePerGuild[$guildId] = array_unique(array_merge(
                        $rolesToRemovePerGuild[$guildId],
                        $roleIds
                    ));
                }
            }
        }

        // Remove the roles from Discord
        foreach ($rolesToRemovePerGuild as $guildId => $roleIds) {
            $this->discordBotProvider->removeRolesFromUser(
                (string)$guildId,
                $member->getDiscordUserId()->value(),
                $roleIds
            );

            $this->logger->info('Removed Discord roles for cancelled subscriptions', [
                'member_id' => $member->getId()->value(),
                'guild_id' => $guildId,
                'role_ids' => $roleIds,
            ]);
        }
    }

    private function removeAllRolesFromMember(\App\Frontend\Domain\Member\Member $member): void
    {
        $guildMemberships = $member->getGuildMemberships();

        foreach ($guildMemberships as $guildMembership) {
            $roleIds = array_map(
                fn($role) => $role->getId()->value(),
                iterator_to_array($guildMembership->getRoles())
            );

            if (!empty($roleIds)) {
                $this->discordBotProvider->removeRolesFromUser(
                    $guildMembership->getGuildId()->value(),
                    $member->getDiscordUserId()->value(),
                    $roleIds
                );

                $this->logger->info('Removed Discord roles for member', [
                    'member_id' => $member->getId()->value(),
                    'guild_id' => $guildMembership->getGuildId()->value(),
                    'role_ids' => $roleIds,
                ]);
            }
        }
    }

    private function subscriptionsAreEqual(
        \App\Frontend\Domain\Stripe\StripeSubscriptions $stored,
        \App\Frontend\Domain\Stripe\StripeSubscriptions $current
    ): bool {
        if ($stored->count() !== $current->count()) {
            return false;
        }

        $storedIds = array_map(
            fn($sub) => $sub->getId(),
            iterator_to_array($stored)
        );
        $currentIds = array_map(
            fn($sub) => $sub->getId(),
            iterator_to_array($current)
        );

        sort($storedIds);
        sort($currentIds);

        return $storedIds === $currentIds;
    }
}
