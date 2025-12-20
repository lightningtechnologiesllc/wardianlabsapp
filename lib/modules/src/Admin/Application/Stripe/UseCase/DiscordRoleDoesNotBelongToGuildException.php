<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\GuildId;
use Exception;

final class DiscordRoleDoesNotBelongToGuildException extends Exception
{
    public function __construct(private readonly DiscordRoleId $roleId, private readonly GuildId $guildId)
    {
        parent::__construct(sprintf('The Discord role with ID %s does not belong to the guild with ID %s.', $this->roleId->value(), $this->guildId->value()));
    }
}
