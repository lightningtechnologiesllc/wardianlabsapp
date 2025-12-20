<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Adapter\Http;

use App\Frontend\Application\Stripe\Webhook\LinkSubscriptionUseCase;
use App\Frontend\Domain\Discord\DiscordUser;
use App\Frontend\Domain\Discord\DiscordUserStore;
use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use App\Shared\Infrastructure\Symfony\WebController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class DiscordConnectController extends WebController
{
    private const SESSION_LINKING_TOKEN_KEY = 'pending_account_linking_token';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Environment $twig,
        private readonly RouterInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly ClientRegistry $clientRegistry,
        private readonly DiscordUserStore $discordUserStore,
        private readonly AccountLinkingTokenRepository $linkingTokenRepository,
        private readonly LinkSubscriptionUseCase $linkSubscriptionUseCase,
    )
    {
        parent::__construct($this->twig, $this->urlGenerator, $this->requestStack);
    }

    #[Route('/connect/discord', name: 'frontend_discord_connect', methods: ['GET'])]
    public function connect(): Response
    {
        $auth2Client = $this->clientRegistry->getClient('discord_main');
        $provider = $auth2Client->getOAuth2Provider();

        $redirectResponse = $auth2Client
            ->redirect([
                'identify', 'email', 'guilds.join'
            ]);

        // We will need something like this to store the state in DB to verify it in the callback from Discord,
        //  because we will use a different domain.
        $this->logger->info('state', [
            'state' => $provider->getState(),
            'redirect_uri' => $redirectResponse->getTargetUrl(),
        ]);

        return $redirectResponse;
    }

    #[Route('/connect/discord/check', name: 'frontend_discord_check', methods: ['GET'])]
    public function check(): Response
    {
        $client = $this->clientRegistry->getClient('discord_main');

        $accessToken = $client->getAccessToken();
        $discordUser = $client->fetchUserFromToken($accessToken);

        $this->saveFromDiscordData($discordUser);

        // Check if this is part of a linking flow
        $session = $this->requestStack->getSession();
        $linkingToken = $session->get(self::SESSION_LINKING_TOKEN_KEY);

        if ($linkingToken) {
            return $this->handleLinking($linkingToken, $discordUser, $accessToken->getToken());
        }

        return $this->redirect('home_get');
    }

    private function handleLinking(string $token, ResourceOwnerInterface $discordUser, string $discordAccessToken): Response
    {
        $session = $this->requestStack->getSession();

        // Get the linking token from repository
        $linkingToken = $this->linkingTokenRepository->findByLinkingToken($token);

        if (!$linkingToken) {
            $this->logger->error('Linking token not found during Discord callback', ['token' => $token]);
            $session->remove(self::SESSION_LINKING_TOKEN_KEY);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'Linking token not found. Please try again.',
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        // Validate token again
        if ($linkingToken->isExpired() || $linkingToken->isLinked()) {
            $this->logger->warning('Token expired or already linked during callback', [
                'token' => $token,
                'is_expired' => $linkingToken->isExpired(),
                'is_linked' => $linkingToken->isLinked(),
            ]);
            $session->remove(self::SESSION_LINKING_TOKEN_KEY);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'This linking token is no longer valid.',
                ]),
                Response::HTTP_CONFLICT
            );
        }

        // Link the subscription
        try {
            ($this->linkSubscriptionUseCase)($linkingToken, $discordUser->getId(), $discordAccessToken);

            $this->logger->info('Successfully linked subscription', [
                'token' => $token,
                'discord_user_id' => $discordUser->getId(),
                'customer_email' => $linkingToken->getCustomerEmail(),
            ]);

            // Clear token from session
            $session->remove(self::SESSION_LINKING_TOKEN_KEY);

            // Redirect to success page
            return new Response(
                $this->twig->render('frontend/account_linking/success.html.twig', [
                    'discord_user' => DiscordUser::fromArray($discordUser->toArray()),
                    'subscription_id' => $linkingToken->getStripeSubscriptionId(),
                ]),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to link subscription', [
                'token' => $token,
                'discord_user_id' => $discordUser->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new Response(
                $this->twig->render('frontend/account_linking/error.html.twig', [
                    'message' => 'An error occurred while linking your account. Please contact support.',
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/connect/discord/disconnect', name: 'frontend_discord_disconnect', methods: ['GET'])]
    public function disconnect(): Response
    {
        $this->discordUserStore->delete();

        return $this->redirect('home_get');
    }

    public function saveFromDiscordData(ResourceOwnerInterface $discordUser): void
    {
        $discordUser = DiscordUser::fromArray($discordUser->toArray());
        $this->discordUserStore->save($discordUser);
    }
}
