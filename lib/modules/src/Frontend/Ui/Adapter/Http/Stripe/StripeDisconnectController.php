<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Adapter\Http\Stripe;

use App\Frontend\Domain\Stripe\StripeUserStore;
use App\Shared\Infrastructure\Symfony\WebController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class StripeDisconnectController extends WebController
{
    public function __construct(
        private readonly Environment     $twig,
        private readonly RouterInterface $urlGenerator,
        private readonly RequestStack    $requestStack,
        private readonly StripeUserStore $stripeUserStore,
    )
    {
        parent::__construct($this->twig, $this->urlGenerator, $this->requestStack);
    }

    #[Route('/connect/stripe/disconnect', name: 'frontend_stripe_disconnect', methods: ['GET'])]
    public function disconnect(Request $request): Response
    {
        $this->stripeUserStore->delete();

        return $this->redirect('home_get');
    }
}
