<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\EventSubscriber;

use App\Admin\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AdminSubscriptionCheckSubscriber implements EventSubscriberInterface
{
    private const EXCLUDED_ROUTES = [
        'admin_login',
        'admin_subscription',
        'admin_discord_connect',
        'admin_discord_connect_check',
        'admin_discord_disconnect',
        'admin_platform_stripe_webhook',
    ];

    private const EXCLUDED_PATH_PREFIXES = [
        '/admin/connect',
        '/admin/stripe/platform-webhook',
    ];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only check admin routes
        if (!str_starts_with($path, '/admin')) {
            return;
        }

        // Check excluded paths
        foreach (self::EXCLUDED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // Check excluded routes
        $route = $request->attributes->get('_route');
        if (in_array($route, self::EXCLUDED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        // Not logged in yet - let security handle it
        if (!$user instanceof User) {
            return;
        }

        // Check if user has active subscription
        if ($user->hasActivePlatformSubscription()) {
            return;
        }

        // Redirect to subscription page
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('admin_subscription'))
        );
    }
}
