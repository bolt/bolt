<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Bolt\Controllers\Cron;

class CronRunner extends AbstractCommand
{
    protected function configure()
    {
        $interims = array('  - cron.Hourly', '  - cron.Daily', '  - cron.Weekly', '  - cron.Monthly', '  - cron.Yearly');
        $this
            ->setName('cron')
            ->setDescription('Cron virtual daemon')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, "Run a particular interim's jobs:\n" . implode("\n", $interims));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('run')) {
            $param = array(
                'run' => true,
                'event' => $input->getOption('run')
            );
        } else {
            $param = array(
                'run' => false,
                'event' => ''
            );
        }

        $result = new Cron($this->app, $output, $param);
        if ($result) {
            $output->writeln("<info>Cron run!</info>");
        }
    }
}
