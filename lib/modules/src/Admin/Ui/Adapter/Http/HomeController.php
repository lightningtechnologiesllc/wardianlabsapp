<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http;

use App\Admin\Application\Stripe\UseCase\GetStripeAccountsForTenantUseCase;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Frontend\Infrastructure\Provider\ApiDiscordUserProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/', name: 'admin_home', methods: ['GET'])]
final class HomeController
{
    public function __construct(
        private readonly Security                          $security,
        private readonly Environment                       $twig,
        private readonly ApiDiscordUserProvider            $apiDiscordUserProvider,
        private readonly GetStripeAccountsForTenantUseCase $useCase,
        private readonly TenantPriceToRolesMappingRepository $priceToRolesMappingRepository,
    )
    {
    }

    public function __invoke(): Response
    {
        /** @var \App\Admin\Domain\User\User $user */
        $user = $this->security->getUser();

        $tenant = $user->getTenants()->first();

        $stripeAccounts = ($this->useCase)($tenant);
        $discordMappings = $this->priceToRolesMappingRepository->findByTenant($tenant->getId());

        // Check if tenant has email configuration
        $tenantConfigured = !empty($tenant->getEmailDSN()) && !empty($tenant->getEmailFromAddress());

        return new Response($this->twig->render('admin/home/index.html.twig', [
            'user' => $user,
            'tenant' => $tenant,
            'stripeAccounts' => $stripeAccounts,
            'current_menu_section' => 'dashboard',
            'onboarding_steps' => [
                'stripe_connected' => $stripeAccounts->count() > 0,
                'discord_connected' => !$discordMappings->isEmpty(),
                'tenant_configured' => $tenantConfigured,
            ],
        ]));
    }
}
