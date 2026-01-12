<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Security;

use App\Admin\Application\Discord\AdminDiscordUserConnector;
use App\Frontend\Domain\Discord\DiscordId;
use KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient as DiscordOauthClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

final class AdminDiscordAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly DiscordOauthClient        $discordOauthClient,
        private readonly RouterInterface           $router,
        private readonly AdminDiscordUserConnector $connector,
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'admin_discord_check';
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->discordOauthClient);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken) {
                /** @var DiscordResourceOwner $discordUser */

                $discordUser = $this->discordOauthClient->fetchUserFromToken($accessToken);

                $discordData = $discordUser->toArray();
                return ($this->connector)(
                    new DiscordId($discordData['id']),
                    $discordData,
                    $accessToken
                );
            })
        );
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $firewallName);
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('admin_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->router->generate('admin_login'));
    }
}
