<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Bus\Event\OutboxPublisher;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:outbox:publish', description: 'Publish pending domain events from a context outbox')]
final class PublishOutboxCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly OutboxPublisher $outboxPublisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('context', InputArgument::REQUIRED, 'order or invoice')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'Keep polling for new events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = $input->getArgument('context');
        if (!\in_array($context, ['order', 'invoice'], true)) {
            $output->writeln('<error>Context must be order or invoice.</error>');

            return Command::INVALID;
        }

        $connection = $this->managerRegistry->getConnection($context);
        $watch = $input->getOption('watch');
        $published = 0;
        do {
            $sent = $this->outboxPublisher->publishNext($connection);
            if ($sent) {
                ++$published;
                $output->writeln('Published a pending event.', OutputInterface::VERBOSITY_VERBOSE);
            } elseif ($watch) {
                usleep(250000);
            }
        } while ($sent || $watch);

        $output->writeln(\sprintf('Published %d event(s).', $published));

        return Command::SUCCESS;
    }
}
