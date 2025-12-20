<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Discord;

use App\Shared\Infrastructure\Symfony\WebController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class DiscordConnectController extends WebController
{
    public function __construct(
        private Environment     $twig,
        private RouterInterface $urlGenerator,
        private RequestStack    $requestStack,
        private Security        $security,
        private ClientRegistry  $clientRegistry,
    )
    {
        parent::__construct($this->twig, $this->urlGenerator, $this->requestStack);
    }

    #[Route('/admin/connect/discord', name: 'admin_discord_connect', methods: ['GET'])]
    public function connect(): Response
    {
        return $this->clientRegistry->getClient('discord_admin')
            ->redirect([
                'identify',
                'email',
                'guilds',
            ]);
    }

    #[Route('/admin/connect/discord/check', name: 'admin_discord_check', methods: ['GET'])]
    public function check(): Response
    {
        return $this->redirect('admin_home');
    }

    #[Route('/admin/connect/discord/disconnect', name: 'admin_discord_disconnect', methods: ['GET'])]
    public function disconnect(): Response
    {
        return $this->security->logout(false);
    }
}
