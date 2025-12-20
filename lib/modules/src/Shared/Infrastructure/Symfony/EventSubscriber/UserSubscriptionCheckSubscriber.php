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

final readonly class UserSubscriptionCheckSubscriber implements EventSubscriberInterface
{
    private const PROTECTED_PATH_PREFIXES = [
        '/admin/discord/',
        '/admin/stripe',
    ];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private string $mainDomain,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getHost() !== $this->mainDomain) {
            return;
        }
        $path = $request->getPathInfo();

        // Only check subscription for protected paths
        $isProtectedPath = false;
        foreach (self::PROTECTED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isProtectedPath = true;
                break;
            }
        }

        if (!$isProtectedPath) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->hasActivePlatformSubscription()) {
            return;
        }

        // Redirect to subscription page
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('admin_subscription'))
        );
    }
}
