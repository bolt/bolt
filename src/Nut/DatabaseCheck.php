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
            ->setDescription('Check the database for missing tables and/or columns.')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $response \Bolt\Storage\Database\Schema\SchemaCheck */
        $response = $this->app['schema']->check();

        if (!$response->hasResponses()) {
            $output->writeln('<info>The database is OK.</info>');
        } else {
            $output->writeln('<comment>Modifications required:</comment>');
            foreach ($response->getResponseStrings() as $messages) {
                $output->writeln('<info> - ' . $messages . '</info>');
            }
            $output->writeln("<comment>One or more fields/tables are missing from the Database. Please run 'nut database:update' to fix this.</comment>");
        }
    }
}
