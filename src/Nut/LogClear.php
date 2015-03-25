<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to clear the system & change logs
 */
class LogClear extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('log:clear')
            ->setDescription('Clear (truncate) the system & change logs.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, no confirmation will be required');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Composer\Command\Helper\DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        $force = $input->getOption('force');

        if (!$force && !$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clear the system & change logs?</question>',
            false
        )) {
            return;
        }

        $this->app['logger.manager']->clear('system');
        $this->app['logger.manager']->clear('change');

        $this->auditLog(__CLASS__, 'System system & change logs cleared');
        $output->writeln("<info>System & change logs cleared!</info>");
    }
}
