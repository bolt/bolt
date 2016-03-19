<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to clear the cache
 */
class CacheClear extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the cache')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['cache']->flushAll();

        $this->auditLog(__CLASS__, 'Cache cleared');

        if ($result) {
            $output->writeln('<info>Cache cleared!</info>');
        } else {
            $output->writeln('<warning>Failed to clear cache. You should delete it manually.</warning>');
        }
    }
}
