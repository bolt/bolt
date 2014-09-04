<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Bolt\Controllers\Cron;

class CronRunner extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('cron')
            ->setDescription('Cron virtual daemon')
            ->addOption('single', null, InputOption::VALUE_NONE, 'If set, tell Bolt cron to run a single task')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of task to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $param = array();

        if ($input->getOption('single')) {
            $param['single'] = true;
        } else {
            $param['single'] = false;
        }

        $name = $input->getArgument('name');
        if ($name) {
            $param['name'] = $name;
        }

        $result = new Cron($this->app, $output, $param);

        if ($result) {
            $output->writeln("<info>Cron run!</info>");
        }
    }
}
