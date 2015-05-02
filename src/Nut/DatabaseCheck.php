<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform a database consistency check command
 */
class DatabaseCheck extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check the database for missing tables and/or columns.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = $this->app['integritychecker']->checkTablesIntegrity();

        if (!empty($messages)) {
            $output->writeln("<info>Modifications required:</info>");
            foreach ($messages as $line) {
                $output->writeln(" - " . str_replace("tt>", "info>", $line) . "");
            }
            $output->writeln("\nOne or more fields/tables are missing from the Database. Please run 'nut database:update' to fix this.");
        } else {
            $output->writeln("\nThe database is OK.");
        }
    }
}
