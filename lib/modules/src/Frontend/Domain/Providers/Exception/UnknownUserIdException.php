<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Providers\Exception;

final class UnknownUserIdException extends \Exception
{
    public function __construct(
        public string $id
    ) {
        parent::__construct(sprintf('Unknown user with ID "%s".', $id));
    }
}
