<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to clear the system & change logs
 */
class LogTrim extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('log:trim')
            ->setDescription('Trim the system & change logs.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app['logger.manager']->trim('system');
        $this->app['logger.manager']->trim('change');

        $this->auditLog(__CLASS__, 'System system & change logs trimmed');
        $output->writeln("<info>System & change logs trimmed!</info>");
    }
}
