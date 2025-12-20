<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Adapter\Http;

use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use App\Shared\Infrastructure\Symfony\WebController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class AccountLinkingController extends WebController
{
    private const SESSION_LINKING_TOKEN_KEY = 'pending_account_linking_token';

    public function __construct(
        private LoggerInterface $logger,
        private Environment $twig,
        private RouterInterface $urlGenerator,
        private RequestStack $requestStack,
        private AccountLinkingTokenRepository $linkingTokenRepository,
    )
    {
        parent::__construct($this->twig, $this->urlGenerator, $this->requestStack);
    }

    #[Route('/link/subscription/{token}', name: 'frontend_link_subscription', methods: ['GET'])]
    public function linkSubscription(string $token): Response
    {
        // Find the linking token
        $linkingToken = $this->linkingTokenRepository->findByLinkingToken($token);

        if (!$linkingToken) {
            $this->logger->warning('Linking token not found', ['token' => $token]);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'Invalid or expired linking token. Please contact support.',
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        // Check if token is expired
        if ($linkingToken->isExpired()) {
            $this->logger->warning('Linking token expired', [
                'token' => $token,
                'expires_at' => $linkingToken->getExpiresAt()->format('Y-m-d H:i:s'),
            ]);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'This linking token has expired. Please make a new subscription or contact support.',
                ]),
                Response::HTTP_GONE
            );
        }

        // Check if already linked
        if ($linkingToken->isLinked()) {
            $this->logger->info('Linking token already used', [
                'token' => $token,
                'linked_at' => $linkingToken->getLinkedAt()?->format('Y-m-d H:i:s'),
            ]);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'This subscription has already been linked to a Discord account.',
                ]),
                Response::HTTP_CONFLICT
            );
        }

        // Store token in session
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_LINKING_TOKEN_KEY, $token);

        $this->logger->info('Stored linking token in session, redirecting to Discord OAuth', [
            'token' => $token,
            'customer_email' => $linkingToken->getCustomerEmail(),
        ]);

        // Redirect to Discord OAuth - the DiscordConnectController will handle the linking flow
        return $this->redirect('frontend_discord_connect');
    }
}
