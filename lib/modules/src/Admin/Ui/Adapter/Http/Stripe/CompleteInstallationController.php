<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Application\Stripe\UseCase\ConnectStripeAccountUseCase;
use App\Admin\Domain\Stripe\PendingStripeInstallationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Twig\Environment;

#[Route('/admin/stripe/complete/{token}', name: 'admin_stripe_complete_installation')]
final readonly class CompleteInstallationController
{
    use TargetPathTrait;

    public function __construct(
        private LoggerInterface                     $logger,
        private RouterInterface                     $router,
        private Security                            $security,
        private PendingStripeInstallationRepository $pendingInstallationRepository,
        private ConnectStripeAccountUseCase         $useCase,
        private Environment                         $twig,
    ) {
    }

    public function __invoke(Request $request, string $token): Response
    {
        $pendingInstallation = $this->pendingInstallationRepository->findByLinkingToken($token);

        if ($pendingInstallation === null) {
            $this->logger->warning('Pending installation not found', ['token' => $token]);
            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'Invalid or expired installation link. Please try installing the Stripe app again.',
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($pendingInstallation->isExpired()) {
            $this->logger->warning('Pending installation expired', [
                'token' => $token,
                'expires_at' => $pendingInstallation->getExpiresAt()->format('Y-m-d H:i:s'),
            ]);
            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'This installation link has expired. Please try installing the Stripe app again.',
                ]),
                Response::HTTP_GONE
            );
        }

        if ($pendingInstallation->isCompleted()) {
            $this->logger->info('Pending installation already completed', ['token' => $token]);
            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'This Stripe installation has already been completed.',
                ]),
                Response::HTTP_CONFLICT
            );
        }

        $user = $this->security->getUser();

        if ($user === null) {
            // Save target path so user returns here after login
            $targetUrl = $this->router->generate(
                'admin_stripe_complete_installation',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $this->saveTargetPath($request->getSession(), 'admin', $targetUrl);

            return new RedirectResponse($this->router->generate('admin_login'));
        }

        $firstTenant = $user->getTenants()->first();

        if ($firstTenant === null || $firstTenant === false) {
            $this->logger->error('User has no tenants', ['user' => $user->getUserIdentifier()]);
            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'You need to create a tenant before connecting a Stripe account.',
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            ($this->useCase)($pendingInstallation->getStripeAccessToken(), $firstTenant->getId());

            $completedInstallation = $pendingInstallation->markAsCompleted();
            $this->pendingInstallationRepository->save($completedInstallation);

            $this->logger->info('Stripe installation completed successfully', [
                'tenant_id' => $firstTenant->getId()->value(),
                'stripe_user_id' => $pendingInstallation->getStripeAccessToken()->stripeUserId,
            ]);

            return new RedirectResponse($this->router->generate('admin_stripe_list_accounts'));
        } catch (\Exception $e) {
            $this->logger->error('Failed to complete Stripe installation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'Failed to complete the Stripe installation. Please try again or contact support.',
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
