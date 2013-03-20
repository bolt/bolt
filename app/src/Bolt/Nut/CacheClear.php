<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClear extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['cache']->clearCache();

        $output->writeln(sprintf("Deleted %s files from cache.\n", $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output->writeln(sprintf("<error>These %s files could not be deleted. You should delete them manually.</error>", $result['failedfiles']));
            foreach($result['failed'] as $failed) {
                $output->writeln(" - $failed");
            }
            $output->writeln("");
        }

        $output->writeln("<info>Cache cleared!</info>");
    }
}
