<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Infrastructure\Symfony\WebController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class StripeController extends WebController
{
    public function __construct(
        private readonly Environment     $twig,
        private readonly RouterInterface $urlGenerator,
        private readonly RequestStack    $requestStack,
        private readonly Security        $security,
    )
    {
        parent::__construct($this->twig, $this->urlGenerator, $this->requestStack);
    }

    #[Route('/admin/connect/stripe', name: 'admin_stripe_connect', methods: ['GET'])]
    public function connect(): Response
    {
        $user = $this->security->getUser();

        return new Response($this->twig->render('admin/stripe/login.html.twig', [
            'user' => $user,
        ]));
    }
}
