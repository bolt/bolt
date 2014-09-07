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
        $this
            ->setName('cron')
            ->setDescription('Cron virtual daemon')
            ->addOption('single', null, InputOption::VALUE_NONE, 'If set, tell Bolt cron to run a single task')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of task to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('single')) {
            $param['single'] = true;
        } else {
            $param['single'] = false;
        }

        $name = $input->getArgument('name');
        if ($name) {
            $param['name'] = $name;
        }

        //$result = $this->app['cron']->execute($param);
        $result = new Cron($this->app, $output);

        if ($result) {
            $output->writeln("<info>Cron run!</info>");
        }
    }
}
