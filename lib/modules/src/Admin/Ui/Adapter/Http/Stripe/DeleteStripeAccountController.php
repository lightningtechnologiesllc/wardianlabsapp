<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\UseCase\DeleteStripeAccountForTenantUseCase;
use App\Frontend\Domain\Tenant\TenantProvider;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Tenant\TenantId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/admin/stripe/accounts/{id}', name: 'admin_stripe_delete_account')]
final class DeleteStripeAccountController
{
    public function __construct(
        private readonly RouterInterface                     $router,
        private readonly Security                            $security,
        private readonly DeleteStripeAccountForTenantUseCase $useCase,
    )
    {
    }

    public function __invoke(string $id): Response
    {
        /** @var \App\Admin\Domain\User\User $user */
        $user = $this->security->getUser();

        ($this->useCase)($user, new StripeAccountId($id));

        return new RedirectResponse($this->router->generate('admin_stripe_list_accounts'));
    }
}
