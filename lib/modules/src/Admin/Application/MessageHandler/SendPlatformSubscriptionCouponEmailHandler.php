<?php
declare(strict_types=1);

namespace App\Admin\Application\MessageHandler;

use App\Admin\Domain\User\PlatformSubscriptionCouponGenerated;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final readonly class SendPlatformSubscriptionCouponEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $platformMailerFrom,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(PlatformSubscriptionCouponGenerated $event): void
    {
        $this->logger->info('Sending platform subscription coupon email', [
            'customer_email' => $event->customerEmail,
            'coupon_code' => $event->couponCode,
            'subscription_id' => $event->subscriptionId,
        ]);


        $email = (new TemplatedEmail())
            ->from(new Address($this->platformMailerFrom, 'WardianLabs'))
            ->to(new Address($event->customerEmail))
            ->subject('Your WardianLabs Access Code')
            ->htmlTemplate('emails/platform_subscription_coupon.html.twig')
            ->context([
                'couponCode' => $event->couponCode,
                'subscriptionId' => $event->subscriptionId,
            ]);

        $this->mailer->send($email);

        $this->logger->info('Platform subscription coupon email sent', [
            'customer_email' => $event->customerEmail,
        ]);
    }
}
