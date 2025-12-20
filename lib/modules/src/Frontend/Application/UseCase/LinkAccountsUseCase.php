<?php
declare(strict_types=1);

namespace App\Frontend\Application\UseCase;

use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
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
use App\Frontend\Domain\Providers\DiscordUserManagerProvider;
use App\Frontend\Domain\Stripe\StripeProvider;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Tenant\TenantId;
use Psr\Log\LoggerInterface;

final readonly class LinkAccountsUseCase
{
    public function __construct(
        private LoggerInterface                     $logger,
        private TenantPriceToRolesMappingRepository $tenantPriceToRolesMappingRepository,
        private StripeProvider                      $stripeProvider,
        private DiscordUserManagerProvider          $discordUserManagerProvider,
        private MemberRepository                    $memberRepository,
    )
    {
    }

    public function __invoke(TenantId $tenantId, string $userEmail, string $discordUserId): array
    {
        $tenantPriceToRolesMappings = $this->tenantPriceToRolesMappingRepository->findByTenant($tenantId);

        $subscriptions = $this->stripeProvider->getValidSubscriptionsForUser($userEmail);

        if ($subscriptions->isEmpty()) {
            $this->logger->info(
                'No valid subscriptions found for user',
                ['email' => $userEmail, 'tenantId' => $tenantId->value()]
            );
            return [];
        }

        $rolesToAssignPerGuild = [];

        if ($tenantPriceToRolesMappings->isEmpty()) {
            $this->logger->warning(
                'Tenant has no Price to Roles Mapping configured',
                ['tenantId' => $tenantId->value()]
            );
            return [];
        }

        /** @var TenantPriceToRolesMapping $mapping */
        foreach ($tenantPriceToRolesMappings->getIterator() as $mapping) {
            foreach ($subscriptions as $subscription) {
                $pricesToRolesMapping = $mapping->getPricesToRolesMapping();

                foreach ($pricesToRolesMapping as $priceId => $roles) {
                    if ($priceId === $subscription->getPlanId()) {
                        foreach ($roles as $role) {
                            $rolesToAssignPerGuild[(string)$mapping->getGuildId()->value()][] = $role;
                        }
                    }
                }

//            $roles = $tenantPriceToRolesMapping->
//                    $role = $planMap->getRoleByPlanId($subscription->getPlanId());
//
//                    if (null !== $role) {
//                        $rolesToAssign[] = $role;
//                    }
            }
        }

//        dump($subscriptions);
//        dd($tenantPriceToRolesMappings);

        if (empty($rolesToAssignPerGuild)) {
            $this->logger->warning(
                'No roles to assign for user',
                ['email' => $userEmail, 'tenantId' => $tenantId->value()]
            );
            return [];
        }

        foreach ($rolesToAssignPerGuild as $guildId => $roleIds) {
            $this->logger->info(
                'Assigning role to user',
                [
                    'email' => $userEmail,
                    'tenantId' => $tenantId->value(),
                    'discordUserId' => $discordUserId,
                    'guildId' => $guildId,
                    'roleId' => $roleIds,
                ]
            );

            $this->discordUserManagerProvider->addRolesToUser(
                (string)$guildId,
                $discordUserId,
                $roleIds
            );

            // Create guild membership with roles
            $roles = array_map(
                fn($roleId) => new DiscordRole(new DiscordRoleId($roleId)),
                $roleIds
            );

            $guildMembership = new GuildMembership(
                new GuildId((string)$guildId),
                new DiscordRoles($roles)
            );

            // Create member already linked to Discord
            $member = Member::createLinked(
                $tenantId,
                new EmailAddress($userEmail),
                $subscriptions,
                new GuildMemberships([$guildMembership]),
                new DiscordId($discordUserId)
            );

            $this->memberRepository->save($member);
        }
        return array_keys($rolesToAssignPerGuild);
    }
}
