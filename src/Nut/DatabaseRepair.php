<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseRepair extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:update')
            ->setDescription('Repair and/or update the database.');
    }

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
        }

        $output->writeln($content);
    }
}
