<?php
declare(strict_types=1);

namespace App\Twig\Components;

use App\Frontend\Application\Service\SendOtpEmail;
use App\Frontend\Domain\Stripe\StripeUser;
use App\Frontend\Domain\Stripe\StripeUserData;
use App\Frontend\Domain\Stripe\StripeUserStore;
use App\Shared\Infrastructure\Validator as AppAssert;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent]
final class StripeConnect
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: ['email', 'otpCode'])]
    #[AppAssert\IsAValidStripeUserData]
    public StripeUserData $stripeUserData;

    #[LiveProp()]
    public bool $hasValidSubscription = false;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly SendOtpEmail $sendOtpEmail,
        private readonly StripeUserStore $stripeUserStore,
    )
    {
    }

    #[LiveAction]
    public function checkEmail(): void
    {
        try {
            $this->validateField('stripeUserData.email');
        } catch (\Exception $e) {
            return;
        }

        $this->sendOtpEmail->__invoke($this->stripeUserData->email);
        $this->hasValidSubscription = true;
    }

    #[LiveAction]
    public function connectEmail(): ?Response
    {
        try {
            $this->validate();
        } catch (\Exception $e) {
            return null;
        }

        $stripeUser = new StripeUser($this->stripeUserData->email);
        $this->stripeUserStore->save($stripeUser);

        return new RedirectResponse($this->router->generate('home_get'));
    }
}
