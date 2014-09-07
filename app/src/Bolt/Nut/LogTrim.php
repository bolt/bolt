<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogTrim extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('log:trim')
            ->setDescription('Trim the activitylog to recent/important items only.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app['log']->trim();

        $output->writeln("<info>Activity logs trimmed!</info>");
    }
}
