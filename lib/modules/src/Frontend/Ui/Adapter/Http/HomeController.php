<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Adapter\Http;

use App\Frontend\Domain\Discord\DiscordUserStore;
use App\Frontend\Domain\Stripe\StripeUserData;
use App\Frontend\Domain\Stripe\StripeUserStore;
use App\Frontend\Domain\Tenant\TenantProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/', name: 'home_get', methods: ['GET'])]
final class HomeController
{
    public function __construct(
        private readonly Security         $security,
        private readonly Environment      $twig,
        private readonly DiscordUserStore $discordUserStore,
        private readonly StripeUserStore  $stripeUserStore,
        private readonly TenantProvider   $tenantProvider,
    )
    {
    }

    public function __invoke(): Response
    {
        try {
            $this->tenantProvider->get();
        } catch (\Exception) {
            return new Response($this->twig->render('frontend/tenant_not_found.html.twig'), Response::HTTP_NOT_FOUND);
        }

        $user = $this->security->getUser();

        $discordUser = $this->discordUserStore->get();

        $stripeUser = $this->stripeUserStore->get();

        return new Response($this->twig->render('home/index.html.twig', [
            'user' => $user,
            'discordUser' => $discordUser,
            'stripeUser' => $stripeUser,
            'stripeUserData' => new StripeUserData(""),
        ]));
    }
}
