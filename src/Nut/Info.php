<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to output phpinfo()
 */
class Info extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Display phpinfo().');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ob_start();
        phpinfo();
        $output->write(ob_get_clean());
    }
}
