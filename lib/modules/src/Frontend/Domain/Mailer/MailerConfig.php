<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Mailer;

use Symfony\Component\Mailer\MailerInterface;

final class MailerConfig
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string          $fromEmail,
        private readonly string          $subjectPrefix,
    )
    {
    }

    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getSubjectPrefix(): string
    {
        return $this->subjectPrefix;
    }
}
