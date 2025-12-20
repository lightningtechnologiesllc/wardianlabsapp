<?php
declare(strict_types=1);

namespace App\Frontend\Ui\Command;

use App\Frontend\Application\Subscription\SyncMemberSubscriptionsUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-member-subscriptions',
    description: 'Syncs member subscriptions with Stripe and updates Discord roles accordingly'
)]
final class SyncMemberSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly SyncMemberSubscriptionsUseCase $syncMemberSubscriptionsUseCase
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting subscription sync</info>');

        ($this->syncMemberSubscriptionsUseCase)();

        $output->writeln('<info>Subscription sync completed</info>');

        return Command::SUCCESS;
    }
}
