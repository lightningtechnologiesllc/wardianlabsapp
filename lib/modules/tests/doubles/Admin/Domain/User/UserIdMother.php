<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Domain\User;


use App\Admin\Domain\User\UserId;

final class UserIdMother
{
    public static function create(?string $value = null): UserId
    {
        return new UserId($value ?? UserId::random()->value());
    }
}
