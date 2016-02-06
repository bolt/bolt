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

        $response = $environment->syncView();
        if ($response === null) {
            $output->writeln('<info>​Directory synchronisation succeeded​.</info>');
        } else {
            $output->writeln('<comment>​Directory synchronisation encountered problems:</comment>');
            foreach ($response as $message) {
                $output->writeln('<comment>' . $message . '</comment>');
            }
        }
    }
}
