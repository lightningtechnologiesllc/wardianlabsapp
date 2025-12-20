<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Discord;

use App\Admin\Application\Stripe\UseCase\GetStripeAccountsForTenantUseCase;
use App\Frontend\Infrastructure\Provider\ApiDiscordUserProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/discord/guilds', name: 'admin_list_discord_servers', methods: ['GET'])]
final readonly class DiscordListServersController
{

    public function __construct(
        private Security                          $security,
        private Environment                       $twig,
        private ApiDiscordUserProvider            $apiDiscordUserProvider,
    )
    {
    }

    public function __invoke(): Response
    {
        /** @var \App\Admin\Domain\User\User $user */
        $user = $this->security->getUser();

        $guilds = $this->apiDiscordUserProvider->getUserGuilds($user->getAccessToken());

        return new Response($this->twig->render('admin/discord/guilds_list.html.twig', [
            'user' => $user,
            'guilds' => $guilds,
            'current_menu_section' => 'discord_servers'
        ]));
    }
}
