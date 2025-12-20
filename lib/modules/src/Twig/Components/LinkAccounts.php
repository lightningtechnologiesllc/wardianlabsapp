<?php
declare(strict_types=1);

namespace App\Twig\Components;

use App\Frontend\Application\UseCase\LinkAccountsUseCase;
use App\Frontend\Domain\Discord\DiscordUserStore;
use App\Frontend\Domain\Stripe\StripeUserStore;
use App\Frontend\Domain\Tenant\TenantProvider;
use App\Frontend\Infrastructure\Persistence\InCode\PlanMap\InCodePlanMapRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\UuidV7;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LinkAccounts
{
    use DefaultActionTrait;

    public function __construct(
        private readonly StripeUserStore     $stripeUserStore,
        private readonly DiscordUserStore    $discordUserStore,
        private readonly LinkAccountsUseCase $linkAccountsUseCase,
        private readonly TenantProvider      $tenantConfigProvider,
    )
    {
    }

    #[LiveAction]
    public function linkUser(): ?RedirectResponse
    {
        $tenant = $this->tenantConfigProvider->get();
        $tenantId = $tenant->getId();

        $stripeUser = $this->stripeUserStore->get();
        $discordUser = $this->discordUserStore->get();

        if ($stripeUser !== null && $discordUser !== null) {
            $guildIds = ($this->linkAccountsUseCase)($tenantId, $stripeUser->email, $discordUser->id);

            if (empty($guildIds)) {
                return new RedirectResponse("https://discord.com/");
            }

            return new RedirectResponse("https://discord.com/channels/{$guildIds[0]}");
        }

        return null;
    }
}
