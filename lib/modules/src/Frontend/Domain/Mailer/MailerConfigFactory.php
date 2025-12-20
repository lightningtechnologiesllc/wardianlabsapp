<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Mailer;

use App\Frontend\Domain\Tenant\TenantProvider;
use Symfony\Component\Mailer\Mailer;
use Exception;

final readonly class MailerConfigFactory
{
    public function __construct(
        private TenantProvider                $tenantProvider,
        private MailerTransportFactory        $mailerTransportFactory,
        #[\SensitiveParameter] private string $defaultMailerDsn,
        private string                        $defaultMailerFrom
    )
    {
    }

    public function create(): MailerConfig
    {
        $tenant = $this->tenantProvider->get();
        return $this->createForTenant($tenant);
    }

    public function createForTenant(\App\Admin\Domain\Tenant\Tenant $tenant): MailerConfig
    {
        $dsn = $tenant->getEmailDSN();
        $fromEmail = $tenant->getEmailFromAddress();

        // Use default DSN if tenant hasn't configured their own
        if (empty($dsn)) {
            $dsn = $this->defaultMailerDsn;
        }

        // Use default from email if tenant hasn't configured their own
        if (empty($fromEmail)) {
            $fromEmail = $this->defaultMailerFrom;
        }

        if (empty($dsn)) {
            throw new Exception("Mailer DSN is not configured. Please set DEFAULT_MAILER_DSN in your environment or configure it for tenant: " . $tenant->getName());
        }

        return new MailerConfig(
            $this->getMailer($dsn),
            $fromEmail,
            "[{$tenant->getName()}]",
        );
    }

    public function getMailer(#[\SensitiveParameter] string $dsn): Mailer
    {
        $transport = $this->mailerTransportFactory->fromDsn($dsn);

        return new Mailer($transport);
    }
}
