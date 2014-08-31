<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionsEnable extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions:enable')
            ->setDescription('Enables an extension.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to enable')
            ->addArgument('version', InputArgument::REQUIRED, 'Version of the extension to enable');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $version = $input->getArgument('version');
        if (!isset($name) || !isset($version)) {
            $output->writeln(
                "<error>".
                $this->app['translator']->trans('You must specify both a name and a version to install!').
                "</error>"
            );
            return;
        }
        
        $result = $this->app['extend.runner']->install($name, $version);
        if (is_array($result)) {
            $output->writeln($result);
            $output->write(" <info>[Done]</info>");
        } else {
            $output->writeln("<error>".$result."<error>");
        }

        
    }
}
