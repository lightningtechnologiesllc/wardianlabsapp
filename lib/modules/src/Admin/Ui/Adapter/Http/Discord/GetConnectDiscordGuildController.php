<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Discord;

use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Admin\Domain\User\User;
use App\Admin\Infrastructure\Provider\Stripe\AccountStripeProvider;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Providers\Exception\UnknownGuildIdException;
use App\Frontend\Infrastructure\Provider\ApiDiscordBotManagerProvider;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route('/admin/discord/guild/{guildId}/connect', name: 'admin_discord_connect_guild', methods: ['GET'])]
final readonly class GetConnectDiscordGuildController
{
    public function __construct(
        private Security                            $security,
        private Environment                         $twig,
        private UrlGeneratorInterface               $urlGenerator,
        private ApiDiscordBotManagerProvider        $apiDiscordBotManagerProvider,
        private AccountStripeProvider               $accountStripeProvider,
        private StripeAccountRepository             $stripeAccountRepository,
        private TenantPriceToRolesMappingRepository $tenantPriceToRolesMappingRepository,
        private string                              $discordClientId,
    )
    {
    }

    public function __invoke(string $guildId): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $firstTenant = $user->getTenants()->first();
        $stripeAccounts = $this->stripeAccountRepository->findByTenantId($firstTenant->getId());

        if ($stripeAccounts->isEmpty()) {
            return new Response($this->twig->render('admin/discord/guild_connect.html.twig', [
                'user' => $user,
                'guildData' => null,
                'current_menu_section' => 'discord_servers',
                'stripeAccount' => null,
                'stripePrices' => null,
                'connectedBot' => false,
                'discordOauthApplicationInstallUrl' => '',
                'tenantPriceToRolesMapping' => null,
                'error' => 'You need to connect a Stripe account first before configuring Discord roles.',
            ]));
        }

        $selectedStripeAccount = $stripeAccounts->first();

        $stripePrices = $this->accountStripeProvider->getPricesForAccount($selectedStripeAccount);

        $connectedBot = true;
        try {
            $guildData = $this->apiDiscordBotManagerProvider->getGuildData(new GuildId($guildId));
        } catch (UnknownGuildIdException $e) {
            $connectedBot = false;
        } catch (Exception $e) {
            $guildData = null;
        }
        $tenantPriceToRolesMappings = $this->tenantPriceToRolesMappingRepository->findByTenant($firstTenant->getId());
        $tenantPriceToRolesMapping = $tenantPriceToRolesMappings->findOneByGuildId(new GuildId($guildId));

        $redirectUrl = $this->urlGenerator->generate('admin_list_discord_servers', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urlData = [
            'client_id' => $this->discordClientId,
            'scope' => "bot",
            'permissions' => "2415921152",
            'redirect_uri' => $redirectUrl,
        ];

        return new Response($this->twig->render('admin/discord/guild_connect.html.twig', [
            'user' => $user,
            'guildData' => $guildData ?? null,
            'current_menu_section' => 'discord_servers',
            'stripeAccount' => $selectedStripeAccount,
            'stripePrices' => $stripePrices,
            'connectedBot' => $connectedBot,
            'discordOauthApplicationInstallUrl' => "https://discord.com/oauth2/authorize?" . http_build_query($urlData),
            'tenantPriceToRolesMapping' => $tenantPriceToRolesMapping,
        ]));
    }
}
