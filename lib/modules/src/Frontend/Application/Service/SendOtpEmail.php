<?php
declare(strict_types=1);

namespace App\Frontend\Application\Service;

use App\Frontend\Domain\Generator\OtpGenerator;
use App\Shared\Domain\Store;
use App\Shared\Infrastructure\Sender\OtpEmailSender;

final readonly class SendOtpEmail
{
    public function __construct(
        private OtpEmailSender $sender,
        private OtpGenerator   $otpGenerator,
        private Store          $store,
    )
    {
    }

    public function __invoke(string $email): void
    {
        $otpCode = $this->otpGenerator->generateOtp();

        $this->store->save($email, ['otpCode' => $otpCode]);

        $this->sender->sendOtp($email, $otpCode);
    }
}
