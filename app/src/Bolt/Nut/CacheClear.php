<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClear extends Command
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the cache');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $result = clearCache();

        $output->writeln(sprintf("Deleted %s files from cache.", $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output->writeln(sprintf("%s files could not be deleted. You should delete them manually.", $result['failedfiles']));
        }

        $output->writeln("Cache cleared!");
    }
}
