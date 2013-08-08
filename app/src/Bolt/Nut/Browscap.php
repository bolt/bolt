<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Browscap extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('browscap')
            ->setDescription('Update our local browscap files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        updateBrowscap();
    }
}
