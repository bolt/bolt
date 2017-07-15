<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut command to clear the system & change logs.
 */
class LogClear extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('log:clear')
            ->setDescription('Clear (truncate) the system & change logs.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Clearing logs');
        $ask = !$input->getOption('no-interaction');
        $question = new ConfirmationQuestion('<question>Are you sure you want to clear the system & change logs?</question>', false);

        if ($ask && !$this->io->askQuestion($question)) {
            return 0;
        }

        $this->app['logger.manager']->clear('system');
        $this->app['logger.manager']->clear('change');

        $this->auditLog(__CLASS__, 'System system & change logs cleared');
        $this->io->success('System & change logs cleared!');

        return 0;
    }
}
