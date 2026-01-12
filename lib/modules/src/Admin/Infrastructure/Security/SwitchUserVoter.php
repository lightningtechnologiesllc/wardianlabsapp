<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Security;

use App\Admin\Domain\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SwitchUserVoter extends Voter
{
    private array $superAdminUsernames;

    public function __construct(string $superAdminUsernames)
    {
        $this->superAdminUsernames = array_filter(
            array_map('trim', explode(',', $superAdminUsernames)),
            fn(string $username) => $username !== ''
        );
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'CAN_SWITCH_USER';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return in_array($user->getUsername(), $this->superAdminUsernames, true);
    }
}
