<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Providers\Exception;

final class UnknownGuildIdException extends \Exception
{
    public function __construct(
        public string $id
    ) {
        parent::__construct(sprintf('Unknown guild with ID "%s".', $id));
    }
}
