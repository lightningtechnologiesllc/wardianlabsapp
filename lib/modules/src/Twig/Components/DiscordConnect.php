<?php
declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DiscordConnect
{
    use DefaultActionTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mainDomain,
    )
    {
    }

    #[LiveAction]
    public function connectDiscord(): Response
    {

        // We will use this to generate a URL with the `generic` omain

//        $context = $this->urlGenerator->getContext();
//        $oldHost = $context->getHost();
//        $oldScheme = $context->getScheme();
//
//        // set the domain you need
//        $context->setHost($this->mainDomain);

        $url = $this->urlGenerator->generate('frontend_discord_connect', [], UrlGeneratorInterface::ABSOLUTE_URL);

//        // restore previous context to avoid affecting other URL generations
//        $context->setHost($oldHost);
//        $context->setScheme($oldScheme);

        return new RedirectResponse($url);
    }
}
