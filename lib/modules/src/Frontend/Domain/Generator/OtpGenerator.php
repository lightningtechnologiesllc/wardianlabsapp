<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Generator;

interface OtpGenerator
{
    public function generateOtp(): string;
}
