<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to clear the cache.
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
        $this->io->title('Flushing Bolt\'s cache');
        $result = $this->app['cache']->flushAll();

        $this->auditLog(__CLASS__, 'Cache cleared');

        if ($result) {
            $this->io->success('Cache cleared!');

            return 0;
        }
        $this->io->error('Failed to clear cache. You should delete it manually.');

        return 1;
    }
}
