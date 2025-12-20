<?php
declare(strict_types=1);

namespace App\Admin\Ui\Command;

use App\Admin\Application\PlatformSubscription\GrantFreePlatformSubscriptionUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:grant-free-platform-subscription',
    description: 'Grant a free platform subscription to a user for testing or support purposes'
)]
final class GrantFreePlatformSubscriptionCommand extends Command
{
    public function __construct(
        private readonly GrantFreePlatformSubscriptionUseCase $grantFreePlatformSubscriptionUseCase
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Discord username of the user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');

        $io->section('Granting free platform subscription');
        $io->text(sprintf('Username: %s', $username));

        try {
            ($this->grantFreePlatformSubscriptionUseCase)($username);
            $io->success(sprintf('Free platform subscription granted to user "%s"', $username));
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
