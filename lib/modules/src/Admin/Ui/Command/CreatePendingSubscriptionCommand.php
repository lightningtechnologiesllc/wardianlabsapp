<?php
declare(strict_types=1);

namespace App\Admin\Ui\Command;

use App\Admin\Application\PlatformSubscription\CreateManualPendingSubscriptionUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-pending-subscription',
    description: 'Manually create a pending platform subscription for a customer who has already paid'
)]
final class CreatePendingSubscriptionCommand extends Command
{
    public function __construct(
        private readonly CreateManualPendingSubscriptionUseCase $createManualPendingSubscriptionUseCase
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Customer email address'
            )
            ->addArgument(
                'subscription-id',
                InputArgument::REQUIRED,
                'Stripe subscription ID (e.g., sub_xxxxx)'
            )
            ->addArgument(
                'plan-id',
                InputArgument::REQUIRED,
                'Stripe price/plan ID (e.g., price_xxxxx)'
            )
            ->addOption(
                'expires',
                null,
                InputOption::VALUE_REQUIRED,
                'Expiration date (Y-m-d format, defaults to 30 days from now)',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $subscriptionId = $input->getArgument('subscription-id');
        $planId = $input->getArgument('plan-id');
        $expiresOption = $input->getOption('expires');

        if ($expiresOption !== null) {
            $expiresAt = new \DateTimeImmutable($expiresOption);
        } else {
            $expiresAt = new \DateTimeImmutable('+30 days');
        }

        $io->section('Creating pending subscription');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Subscription ID', $subscriptionId],
                ['Plan ID', $planId],
                ['Expires At', $expiresAt->format('Y-m-d H:i:s')],
            ]
        );

        $pendingSubscription = ($this->createManualPendingSubscriptionUseCase)(
            customerEmail: $email,
            subscriptionId: $subscriptionId,
            planId: $planId,
            expiresAt: $expiresAt,
        );

        $io->success('Pending subscription created successfully!');
        $io->block(
            sprintf('Coupon Code: %s', $pendingSubscription->getCouponCode()),
            null,
            'fg=black;bg=green',
            ' ',
            true
        );

        $io->note('Share this coupon code with the customer so they can redeem their subscription.');

        return Command::SUCCESS;
    }
}
