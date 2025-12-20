<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Domain\Member;

use App\Frontend\Domain\Member\MemberId;

final class MemberIdMother
{
    public static function create(?string $value = null): MemberId
    {
        return new MemberId($value ?? MemberId::random()->value());
    }
}
