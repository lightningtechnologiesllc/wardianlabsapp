<?php
declare(strict_types=1);

namespace App\Admin\Ui\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class TenantConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'help' => 'This is your tenant\'s name.',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a name'),
                    new Assert\Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Name must be at least {{ limit }} characters',
                        maxMessage: 'Name cannot be longer than {{ limit }} characters'
                    ),
                ],
                'attr' => [
                    'placeholder' => 'My Community',
                    'class' => 'block min-w-0 grow bg-white py-1.5 pr-3 pl-1 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm/6 dark:bg-white/5 dark:text-white',
                ],
            ])
            ->add('subdomain', TextType::class, [
                'label' => 'Subdomain',
                'help' => 'This is what your users will use to access your tenant.',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a subdomain'),
                    new Assert\Length(
                        min: 3,
                        max: 50,
                        minMessage: 'Subdomain must be at least {{ limit }} characters',
                        maxMessage: 'Subdomain cannot be longer than {{ limit }} characters'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-z0-9-]+$/',
                        message: 'Subdomain can only contain lowercase letters, numbers and hyphens'
                    ),
                ],
                'attr' => [
                    'placeholder' => 'my-community',
                    'class' => 'block min-w-0 grow text-right bg-white py-1.5 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm/6 dark:bg-white/5 dark:text-white',
                ],
            ])
            ->add('useCustomEmail', CheckboxType::class, [
                'label' => 'Use custom email configuration',
                'help' => 'Enable this to configure your own email settings. By default, the system uses shared email settings.',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-white/10 dark:bg-white/5',
                    'data-action' => 'change->toggle-email-fields#toggle',
                ],
            ])
            ->add('emailDSN', TextType::class, [
                'label' => 'Email DSN',
                'help' => 'Your tenant\'s email DSN. Format: activecampaign+api://clientId:apiKey@null or smtp://user:pass@smtp.example.com:port',
                'required' => false,
                'attr' => [
                    'placeholder' => 'activecampaign+api://clientId:apiKey@null',
                    'class' => 'block min-w-0 grow bg-white py-1.5 pr-3 pl-1 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm/6 dark:bg-white/5 dark:text-white',
                ],
            ])
            ->add('emailFromAddress', EmailType::class, [
                'label' => 'Email From Address',
                'help' => 'The address where emails will be sent from.',
                'required' => false,
                'attr' => [
                    'placeholder' => 'username@domain.com',
                    'class' => 'block min-w-0 grow bg-white py-1.5 pr-3 pl-1 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm/6 dark:bg-white/5 dark:text-white',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'tenant_config',
        ]);
    }
}
