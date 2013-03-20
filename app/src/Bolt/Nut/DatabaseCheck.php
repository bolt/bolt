<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCheck extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check the database for missing columns.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['storage']->checkTablesIntegrity();

        if ($result !== true) {
            $output->writeln("<info>Modifications required:</info>");
            foreach($result as $line) {
                $output->writeln(" - " . str_replace("tt>", "info>", $line) . "");
            }
            $output->writeln("\nOne or more fields/tables are missing from the Database. Please run 'nut database:update' to fix this.");
        } else {
            $output->writeln("\nThe database is OK.");
        }
    }
}
