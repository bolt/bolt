<?php

namespace Bolt\Nut;

use Bolt\Controllers\Cron;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to run cron tasks
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CronRunner extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $interims = array('  - cron.Hourly', '  - cron.Daily', '  - cron.Weekly', '  - cron.Monthly', '  - cron.Yearly');
        $this
            ->setName('cron')
            ->setDescription('Cron virtual daemon')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, "Run a particular interim's jobs:\n" . implode("\n", $interims));
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('run')) {
            $event = $input->getOption('run');
            $param = array(
                'run'   => true,
                'event' => $event
            );
        } else {
            $param = array(
                'run'   => false,
                'event' => ''
            );
        }

        $result = new Cron($this->app, $output, $param);
        if ($result) {
            if ($event) {
                $this->auditLog(__CLASS__, "Cron $event job run");
            } else {
                $this->auditLog(__CLASS__, 'Cron run');
            }
            $output->writeln("<info>Cron run!</info>");
        }
    }
}
