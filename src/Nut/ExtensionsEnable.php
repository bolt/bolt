<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to install an extension
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionsEnable extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('extensions:enable')
            ->setAliases(array('extensions:install'))
            ->setDescription('Installs an extension by name and version.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to enable')
            ->addArgument('version', InputArgument::REQUIRED, 'Version of the extension to enable');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $version = $input->getArgument('version');

        $result = $this->app['extend.manager']->requirePackage(array('name' => $name, 'version' => $version));

        if ($result === 0) {
            $this->auditLog(__CLASS__, "Installed extension $name");
        }

        $output->writeln("<info>[Done]</info> ");
        $output->writeln($result, OutputInterface::OUTPUT_PLAIN);
    }
}
