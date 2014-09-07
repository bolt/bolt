<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Display phpinfo().');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        phpinfo();
    }
}
