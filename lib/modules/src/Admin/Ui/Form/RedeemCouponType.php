<?php
declare(strict_types=1);

namespace App\Admin\Ui\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class RedeemCouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('couponCode', TextType::class, [
                'label' => 'Coupon Code',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your coupon code'),
                    new Assert\Regex(
                        pattern: '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/i',
                        message: 'Invalid coupon code format (expected: XXXX-XXXX-XXXX)'
                    ),
                ],
                'attr' => [
                    'placeholder' => 'XXXX-XXXX-XXXX',
                    'class' => 'block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-white/5 dark:text-white dark:outline-white/10',
                    'style' => 'text-transform: uppercase;',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'redeem_coupon',
        ]);
    }
}
