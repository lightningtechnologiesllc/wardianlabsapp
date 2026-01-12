<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\StripeAccessTokenFactory;
use App\Admin\Application\Stripe\UseCase\ConnectStripeAccountUseCase;
use App\Admin\Domain\Stripe\PendingStripeInstallation;
use App\Admin\Domain\Stripe\PendingStripeInstallationRepository;
use App\Admin\Infrastructure\Provider\Stripe\AccountStripeProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[Route('/admin/stripe/callback', name: 'admin_stripe_installation_callback')]
final readonly class InstallationCallbackController
{
    private OAuth2ClientInterface $oauthClient;

    public function __construct(
        private LoggerInterface                   $logger,
        private RouterInterface                   $router,
        private Security                          $security,
        private ClientRegistry                    $clientRegistry,
        private ConnectStripeAccountUseCase       $useCase,
        private PendingStripeInstallationRepository $pendingInstallationRepository,
        private AccountStripeProvider             $accountStripeProvider,
        private MailerInterface                   $mailer,
        private Environment                       $twig,
        private string                            $defaultMailerFrom,
    ) {
        $this->oauthClient = $this->clientRegistry->getClient('stripe_admin');
    }

    public function __invoke(Request $request): Response
    {
        $this->logger->info('Stripe installation callback received', ["request" => $request->query->all()]);

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

            $message = 'The authorization code has expired or was already used. Please try installing the Stripe app again.';
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                $message = 'The authorization link has expired or was already used. If you already started the installation, please check your email for a completion link.';
            }

            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => $message,
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        $stripeAccessToken = StripeAccessTokenFactory::createFromLeague($accessToken);

        $user = $this->security->getUser();

        if ($user === null) {
            return $this->handleUnauthenticatedUser($stripeAccessToken, $request);
        }

        return $this->handleAuthenticatedUser($stripeAccessToken, $user);
    }

    private function handleUnauthenticatedUser(\App\Shared\Domain\Stripe\StripeAccessToken $stripeAccessToken, Request $request): Response
    {
        $email = $this->accountStripeProvider->getAccountEmail($stripeAccessToken);

        if ($email === null) {
            // Store the access token in session and ask for email
            $request->getSession()->set('pending_stripe_access_token', $stripeAccessToken->toArray());

            $this->logger->info('No email found for Stripe account, asking user to provide email', [
                'stripe_user_id' => $stripeAccessToken->stripeUserId,
            ]);

            return new Response(
                $this->twig->render('admin/stripe/installation_ask_email.html.twig'),
                Response::HTTP_OK
            );
        }

        $pendingInstallation = PendingStripeInstallation::create($stripeAccessToken, $email);
        $this->pendingInstallationRepository->save($pendingInstallation);

        $this->sendInstallationEmail($pendingInstallation);

        $this->logger->info('Created pending Stripe installation', [
            'email' => $email,
            'stripe_user_id' => $stripeAccessToken->stripeUserId,
            'linking_token' => $pendingInstallation->getLinkingToken(),
        ]);

        return new Response(
            $this->twig->render('admin/stripe/installation_pending.html.twig', [
                'email' => $email,
            ]),
            Response::HTTP_OK
        );
    }

    private function handleAuthenticatedUser(
        \App\Shared\Domain\Stripe\StripeAccessToken $stripeAccessToken,
        mixed $user
    ): Response {
        $firstTenant = $user->getTenants()->first();

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

    private function sendInstallationEmail(PendingStripeInstallation $pendingInstallation): void
    {
        $completeUrl = $this->router->generate(
            'admin_stripe_complete_installation',
            ['token' => $pendingInstallation->getLinkingToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $emailContent = $this->twig->render('admin/emails/stripe_installation_pending.html.twig', [
            'complete_url' => $completeUrl,
            'expires_at' => $pendingInstallation->getExpiresAt(),
        ]);

        $email = (new Email())
            ->from($this->defaultMailerFrom)
            ->to($pendingInstallation->getEmail())
            ->subject('Complete Your Stripe Installation')
            ->html($emailContent);

        try {
            $this->mailer->send($email);
            $this->logger->info('Sent Stripe installation email', [
                'email' => $pendingInstallation->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send Stripe installation email', [
                'email' => $pendingInstallation->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
