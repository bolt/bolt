<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogClear extends BaseCommand
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
        /** @var \Composer\Command\Helper\DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        $force = $input->getOption('force');

        if (!$force && !$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clear the activity log?</question>',
            false
        )) {
            return;
        }

        $this->app['logger.manager']->clear('system');
        $this->app['logger.manager']->clear('change');

        $output->writeln("<info>Activity logs cleared!</info>");
    }
}
