<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use App\Frontend\Infrastructure\Provider\HttpStripeProvider;
use App\Shared\Domain\Store;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class IsAValidStripeUserDataValidator extends ConstraintValidator
{
    public function __construct(
        private readonly HttpStripeProvider $stripeProvider,
        private readonly Store              $store,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsAValidStripeUserData) {
            throw new UnexpectedTypeException($constraint, IsAValidStripeUserData::class);
        }
        if (null === $value->email || '' === $value->email) {
            $this->context->buildViolation("The email field cannot be empty.")
                ->atPath('email')
                ->addViolation();
            return;
        }

        if(!$this->stripeProvider->hasValidSubscription($value->email)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('email')
                ->setParameter('{{ string }}', $value->email)
                ->addViolation();
        }

        if ("" === $value->otpCode || null === $value->otpCode) {
            $this->context->buildViolation("The OTP code field cannot be empty.")
                ->atPath('otpCode')
                ->addViolation();
            return;
        }

        $user = $this->store->get($value->email);
        if ($user['otpCode'] !== $value->otpCode) {
            $this->context->buildViolation("The OTP code is invalid.")
                ->atPath('otpCode')
                ->addViolation();
        }
    }
}
