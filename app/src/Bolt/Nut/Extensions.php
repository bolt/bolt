<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Extensions extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions')
            ->setDescription('Lists all installed extensions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['extend.runner']->installed();
        print_r($result); exit;
        $output->writeln(implode("\n", $lines));
    }
}
