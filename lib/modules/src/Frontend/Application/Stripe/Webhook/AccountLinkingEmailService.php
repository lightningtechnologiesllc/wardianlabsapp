<?php
declare(strict_types=1);

namespace App\Frontend\Application\Stripe\Webhook;

use App\Admin\Domain\Tenant\Tenant;
use App\Frontend\Domain\Mailer\MailerConfigFactory;
use App\Shared\Domain\Stripe\AccountLinkingToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccountLinkingEmailService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private MailerConfigFactory $mailerConfigFactory,
    )
    {
    }

    public function __invoke(Tenant $tenant, AccountLinkingToken $linkingToken): void
    {
        try {
            // Get mailer config for specific tenant (with default fallback)
            $mailerConfig = $this->mailerConfigFactory->createForTenant($tenant);

            // Generate linking URL
            $linkingUrl = $this->urlGenerator->generate(
                'frontend_link_subscription',
                ['token' => $linkingToken->getLinkingToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Create email
            $email = (new \Symfony\Component\Mime\Email())
                ->from($mailerConfig->getFromEmail())
                ->to($linkingToken->getCustomerEmail())
                ->subject($mailerConfig->getSubjectPrefix() . ' Link your subscription to Discord')
                ->html($this->getEmailHtml($linkingUrl, $linkingToken));

            // Send email
            $mailerConfig->getMailer()->send($email);

            $this->logger->info('Sent account linking email', [
                'customer_email' => $linkingToken->getCustomerEmail(),
                'tenant_id' => $tenant->getId()->value(),
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send account linking email', [
                'error' => $e->getMessage(),
                'customer_email' => $linkingToken->getCustomerEmail(),
                'tenant_id' => $tenant->getId()->value(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error sending account linking email', [
                'error' => $e->getMessage(),
                'customer_email' => $linkingToken->getCustomerEmail(),
                'tenant_id' => $tenant->getId()->value(),
            ]);
        }
    }

    private function getEmailHtml(string $linkingUrl, AccountLinkingToken $linkingToken): string
    {
        $expiresAt = $linkingToken->getExpiresAt()->format('F j, Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #5865F2 !important;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer { margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Link Your Subscription to Discord</h2>

        <p>Thank you for your subscription!</p>

        <p>To get access to your Discord roles, please click the button below to link your subscription to your Discord account:</p>

        <a href="{$linkingUrl}" class="button" style="background-color: #5865F2; color: #ffffff; text-decoration: none; display: inline-block; padding: 12px 24px; border-radius: 4px; margin: 20px 0; font-weight: bold;">Link Discord Account</a>

        <p>You will be asked to log in with Discord and authorize the connection.</p>

        <p><strong>Important:</strong> This link will expire on {$expiresAt}.</p>

        <div class="footer">
            <p>If you did not subscribe, please ignore this email.</p>
            <p>If the button doesn't work, copy and paste this URL into your browser:<br>
            {$linkingUrl}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
