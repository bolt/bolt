<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to repair/update database schema
 */
class DatabaseRepair extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('database:update')
            ->setDescription('Repair and/or update the database.')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $response = $this->app['schema']->update();

        if (!$response->hasResponses()) {
            $output->writeln('<info>Your database is already up to date.</info>');
        } else {
            $output->writeln('<comment>Modifications made to the database:</comment>');
            foreach ($response->getResponseStrings() as $messages) {
                $output->writeln('<info> - ' . $messages . '</info>');
            }
            $output->writeln('<info>Your database is now up to date.</info>');

            $this->auditLog(__CLASS__, 'Database updated');
        }
    }
}
