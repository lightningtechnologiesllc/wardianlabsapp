<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Subscription;

use App\Admin\Application\PlatformSubscription\InvalidCouponException;
use App\Admin\Application\PlatformSubscription\RedeemCouponUseCase;
use App\Admin\Domain\User\User;
use App\Admin\Ui\Form\RedeemCouponType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route('/admin/subscription', name: 'admin_subscription', methods: ['GET', 'POST'])]
final readonly class SubscriptionController
{
    public function __construct(
        private Security $security,
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private RedeemCouponUseCase $redeemCouponUseCase,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // If user already has active subscription, redirect to home
        if ($user->hasActivePlatformSubscription()) {
            return new RedirectResponse($this->urlGenerator->generate('admin_home'));
        }

        $form = $this->formFactory->create(RedeemCouponType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $couponCode = strtoupper($form->get('couponCode')->getData());
            try {
                ($this->redeemCouponUseCase)($user, $couponCode);
                $request->getSession()->getFlashBag()->add('success', 'Your subscription has been activated successfully!');

                return new RedirectResponse($this->urlGenerator->generate('admin_home'));
            } catch (InvalidCouponException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
            }
        }

        return new Response($this->twig->render('admin/subscription/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]));
    }
}
