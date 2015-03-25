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
            ->setDescription('Repair and/or update the database.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['integritychecker']->repairTables();

        if (empty($result)) {
            $content = "<info>Your database is already up to date.</info>";
        } else {
            $content = "<info>Modifications made to the database:</info>\n";
            foreach ($result as $line) {
                $content .= ' - ' . str_replace('tt>', 'info>', $line) . "\n";
            }
            $content .= "<info>Your database is now up to date.</info>";

            $this->auditLog(__CLASS__, 'Database updated');
        }

        $output->writeln($content);
    }
}
