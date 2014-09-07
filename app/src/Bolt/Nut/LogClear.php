<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class LogClear extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('log:clear')
            ->setDescription('Clear (truncate) the activitylog.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, no confirmation will be required');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $force = $input->getOption('force');

        if (!$force && !$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clear the activity log?</question>',
            false
        )) {
            return;
        }

        $this->app['log']->clear();

        $output->writeln("<info>Activity logs cleared!</info>");
    }
}
