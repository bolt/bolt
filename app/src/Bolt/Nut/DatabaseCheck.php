<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class DatabaseCheck extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check and repair/update the database.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['storage']->repairTables();

        if (empty($result)) {
            $content = "<info>Your database is already up to date.</info>";
        } else {
            $content = "<info>Modifications made to the database:</info>\n";
            foreach($result as $line) {
                $content .= " - ". strip_tags($line) . "\n";
            }
            $content .= "<info>Your database is now up to date.</info>";
        }

        $output->writeln($content);
    }
}
