<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Generator;

use App\Frontend\Domain\Generator\OtpGenerator;

final class FakeOtpGenerator implements OtpGenerator
{
    public function generateOtp(): string
    {
        return "123456";
    }
}
