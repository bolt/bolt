<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform Bolt web asset sync.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SetupSync extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup:sync')
            ->setDescription('Synchronise a Bolt install private asset directories with the web root.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Configuration\Environment $environment */
        $environment = $this->app['config.environment'];

        $response = $environment->syncAssets();
        if ($response === null) {
            $this->io->success('Directory synchronisation succeeded​.');

            return 0;
        }

        $this->io->error('​Directory synchronisation encountered problems');
        $this->io->error($response);

        return 1;
    }
}
