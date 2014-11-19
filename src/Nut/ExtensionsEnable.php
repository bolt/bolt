<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bolt\Translation\Translator as Trans;

class ExtensionsEnable extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions:enable')
            ->setAliases(array('extensions:install'))
            ->setDescription('Installs an extension by name and version.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to enable')
            ->addArgument('version', InputArgument::REQUIRED, 'Version of the extension to enable');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $version = $input->getArgument('version');
        if (!isset($name) || !isset($version)) {
            $output->writeln(
                '<error>' .
                Trans::__('You must specify both a name and a version to install!') .
                '</error>'
            );

            return;
        }

        $result = $this->app['extend.runner']->install($name, $version);

        $output->writeln("<info>[Done]</info> ");
        $output->writeln($result, OutputInterface::OUTPUT_PLAIN);

    }
}
