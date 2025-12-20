<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Sender;

use App\Frontend\Domain\Mailer\MailerConfigFactory;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Twig\Environment;

final readonly class OtpEmailSender
{
    public function __construct(
        private MailerConfigFactory $mailerConfigFactory,
        private Environment $twig
    )
    {
    }

    public function sendOtp(string $toEmail, string $otpCode): void
    {
        $mailerConfig = $this->mailerConfigFactory->create();

        $email = (new TemplatedEmail())
            ->from($mailerConfig->getFromEmail())
            ->to(new Address($toEmail))
            ->subject("{$mailerConfig->getSubjectPrefix()} verifica tu email " . $otpCode)
            ->htmlTemplate('emails/otp.html.twig')
            ->context([
                'otpCode' => $otpCode,
            ]);

        $twigBodyRenderer = new BodyRenderer($this->twig);
        $twigBodyRenderer->render($email);

        $mailerConfig->getMailer()->send($email);
    }
}
