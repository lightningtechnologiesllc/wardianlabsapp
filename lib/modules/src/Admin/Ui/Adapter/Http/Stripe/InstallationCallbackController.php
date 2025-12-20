<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\StripeAccessTokenFactory;
use App\Admin\Application\Stripe\UseCase\ConnectStripeAccountUseCase;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/admin/stripe/callback', name: 'admin_stripe_installation_callback')]
final readonly class InstallationCallbackController
{
    private OAuth2ClientInterface $oauthClient;

    public function __construct(
        private readonly LoggerInterface                $logger,
        private readonly RouterInterface                $router,
        private Security                                $security,
        private readonly ClientRegistry                 $clientRegistry,
        private readonly ConnectStripeAccountUseCase    $useCase,
    )
    {
        $this->oauthClient = $this->clientRegistry->getClient('stripe_admin');
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        $firstTenant = $user->getTenants()->first();
        $this->logger->info('Stripe installation callback received', ["request" => $request->request->all()]);

        if ($request->query->has('error')) {
            $this->logger->warning('Stripe installation cancelled or failed', [
                'error' => $request->query->get('error'),
                'error_description' => $request->query->get('error_description'),
            ]);

            return new RedirectResponse($this->router->generate('admin_stripe_list_accounts'));
        }

        try {
            $accessToken = $this->oauthClient->getAccessToken();
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch Stripe access token', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        $stripeAccessToken = StripeAccessTokenFactory::createFromLeague($accessToken);

        try {
            ($this->useCase)($stripeAccessToken, $firstTenant->getId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect Stripe account', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        $this->logger->info('Stripe account connected successfully', [
            'tenant_id' => $firstTenant->getId()->value(),
            'stripe_account_id' => $stripeAccessToken->stripeUserId,
        ]);

        return new RedirectResponse($this->router->generate('admin_stripe_list_accounts'));
    }
}
