<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendCheck extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extend:check')
            ->setDescription('Checks for updates to installed extensions');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = \evidev\composer\Wrapper::create();
        $result = $composer->run("update --dry-run", $output);

    }
}
