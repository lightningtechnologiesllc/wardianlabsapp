<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Admin\Infrastructure\Provider\Stripe\AccountStripeProvider;
use App\Admin\Infrastructure\Provider\Stripe\StripePrices;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Infrastructure\Provider\ApiDiscordBotManagerProvider;
use App\Shared\Domain\Stripe\StripeAccount;

final readonly class AssignPricesToRolesUseCase
{
    public function __construct(
        private AccountStripeProvider               $accountStripeProvider,
        private ApiDiscordBotManagerProvider        $apiDiscordBotManagerProvider,
        private TenantPriceToRolesMappingRepository $tenantPriceToRolesMappingRepository,
    )
    {
    }

    public function __invoke(Tenant $tenant, StripeAccount $account, GuildId $guildId, array $pricesToRolesMapping): void
    {
        $guildData = $this->apiDiscordBotManagerProvider->getGuildData($guildId);
        $stripePrices = $this->accountStripeProvider->getPricesForAccount($account);

        $this->checkThatPricesExistInStripeAccount($pricesToRolesMapping, $stripePrices, $account);
        $this->checkThatDiscordRolesExistInGuild($pricesToRolesMapping, $guildData, $guildId);

        $mapping = new TenantPriceToRolesMapping($tenant->getId(), $guildId, $pricesToRolesMapping);
        $this->tenantPriceToRolesMappingRepository->save($mapping);

//        dd($mapping);


//        dd($account, $guildData, $stripePrices, $pricesToRolesMapping);
    }

    private function checkThatPricesExistInStripeAccount(array $pricesToRolesMapping, StripePrices $stripePrices, StripeAccount $account): void
    {
        foreach ($pricesToRolesMapping as $priceId => $roleId) {
            if (!$stripePrices->priceExists($priceId)) {
                throw new PriceDoesNotBelongToStripeAccountException($priceId, $account->getAccountId());
            }
        }
    }

    private function checkThatDiscordRolesExistInGuild(array $pricesToRolesMapping, array $guildData, GuildId $guildId): void
    {
        $guildDataRolesIds = array_map(function($role) {
            return $role['id'];
        }, $guildData['roles']);

        foreach ($pricesToRolesMapping as $priceId => $roles) {
            foreach ($roles as $roleId) {
                if (!in_array($roleId, $guildDataRolesIds)) {
                    throw new DiscordRoleDoesNotBelongToGuildException(new DiscordRoleId($roleId), $guildId);
                }
            }
        }
    }
}
