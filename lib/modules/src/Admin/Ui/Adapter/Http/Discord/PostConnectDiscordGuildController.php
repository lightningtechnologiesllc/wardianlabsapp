<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Discord;

use App\Admin\Application\Stripe\UseCase\AssignPricesToRolesUseCase;
use App\Admin\Domain\User\User;
use App\Frontend\Domain\Discord\GuildId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Infrastructure\Symfony\WebController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route('/admin/discord/guild/{guildId}/connect', name: 'post_admin_discord_connect_guild', methods: ['POST'])]
final readonly class PostConnectDiscordGuildController extends WebController
{
    public function __construct(
        private Security                   $security,
        private Environment                $twig,
        private UrlGeneratorInterface      $urlGenerator,
        private RequestStack               $requestStack,
        private StripeAccountRepository    $stripeAccountRepository,
        private AssignPricesToRolesUseCase $assignPricesToRolesUseCase
    )
    {
        parent::__construct($twig, $urlGenerator, $requestStack);
    }

    public function __invoke(string $guildId, Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $firstTenant = $user->getTenants()->first();
        $stripeAccounts = $this->stripeAccountRepository->findByTenantId($firstTenant->getId());
        $selectedStripeAccount = $stripeAccounts->first();

        ($this->assignPricesToRolesUseCase)($firstTenant, $selectedStripeAccount, new GuildId($guildId), $request->request->all());

        return $this->redirectWithMessage("admin_discord_connect_guild", ['guildId' => $guildId], 'Prices successfully assigned to roles.');
    }
}
