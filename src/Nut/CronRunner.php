<?php

namespace Bolt\Nut;

use Bolt\Cron;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to run cron tasks.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CronRunner extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $interims = ['  - cron.Minute', '  - cron.Hourly', '  - cron.Daily', '  - cron.Weekly', '  - cron.Monthly', '  - cron.Yearly'];
        $this
            ->setName('cron')
            ->setDescription('Cron virtual daemon')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, "Run a particular interim's jobs:\n" . implode("\n", $interims))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('run')) {
            $event = $input->getOption('run');
            $param = [
                'run'   => true,
                'event' => $event,
            ];
        } else {
            $event = false;
            $param = [
                'run'   => false,
                'event' => '',
            ];
        }

        $result = new Cron($this->app, $output);
        if ($result->execute($param)) {
            if ($event) {
                $message = sprintf('Cron "%s" job run', $event);
            } else {
                $message = 'Cron run';
            }
            $this->io->success($message);
            $this->auditLog(__CLASS__, $message);

            return 0;
        }

        return 1;
    }
}
