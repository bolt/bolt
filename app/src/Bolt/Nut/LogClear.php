<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogClear extends Command
{
    protected function configure()
    {
        $this
            ->setName('log:clear')
            ->setDescription('Clear (truncate) the activitylog.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $app;

        $app['log']->clear();

        $output->writeln("<info>Activity logs trimmed!</info>");

    }
}
