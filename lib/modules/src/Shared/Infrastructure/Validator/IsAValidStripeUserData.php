<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class IsAValidStripeUserData extends Constraint
{
    public string $message = 'The address "{{ string }}" has no active Stripe Subscription.';

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
