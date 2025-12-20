<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Generator;

use App\Frontend\Domain\Generator\OtpGenerator;

final class RandomOtpGenerator implements OtpGenerator
{
    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
