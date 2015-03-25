<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to uninstall an extension
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionsDisable extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('extensions:disable')
            ->setAliases(array('extensions:uninstall'))
            ->setDescription('Uninstalls an extension.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to uninstall');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $result = $this->app['extend.manager']->removePackage(array($name));

        if ($result === 0) {
            $this->auditLog(__CLASS__, "Removed extension $name");
        }
        $output->writeln('<info>' . $result . '</info>', OutputInterface::OUTPUT_PLAIN);
    }
}
