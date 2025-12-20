<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\UseCase\GetStripeAccountsForTenantUseCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/stripe/accounts', name: 'admin_stripe_list_accounts')]
final readonly class ListStripeAccountsController
{
    public function __construct(
        private Security                          $security,
        private Environment $twig,
        private GetStripeAccountsForTenantUseCase $useCase,
    )
    {
    }

    public function __invoke(): Response
    {
        $user = $this->security->getUser();
        $firstTenant = $user->getTenants()->first();

        $stripeAccounts = ($this->useCase)($firstTenant);

        return new Response($this->twig->render('admin/stripe/accounts/index.html.twig', [
            'user' => $user,
            'stripeAccounts' => $stripeAccounts,
            'current_menu_section' => 'stripe_accounts'
        ]));
    }
}
