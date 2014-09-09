<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCheck extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check the database for missing tables and/or columns.');
    }

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
