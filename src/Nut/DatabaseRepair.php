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
        $messages = [];
        $responses = $this->app['integritychecker']->repairTables();

        foreach ($responses as $response) {
            if ($response->hasMessages()) {
                $messages[] = $response->getTitle() . ' ' . implode(', ', $response->getMessages());
            }
        }

        if (empty($messages)) {
            $content = "<info>Your database is already up to date.</info>";
        } else {
            $content = "<info>Modifications made to the database:</info>\n";
            foreach ($messages as $line) {
                $content .= ' - ' . str_replace('tt>', 'info>', $line) . "\n";
            }
            $content .= "<info>Your database is now up to date.</info>";

            $this->auditLog(__CLASS__, 'Database updated');
        }

        $output->writeln($content);
    }
}
