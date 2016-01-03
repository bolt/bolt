<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to uninstall an extension
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsUninstall extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:uninstall')
            ->setAliases(['extensions:disable'])
            ->setDescription('Uninstalls an extension.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to uninstall')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $output->write("\n<info>Starting uninstall of {$name}… </info>");

        $result = $this->app['extend.manager']->removePackage([$name]);
        if ($result === 0) {
            $output->write("<info>[DONE]</info>\n");
            $this->auditLog(__CLASS__, "Removed extension $name");
        } else {
            $output->write("<error>[FAILED]</error>\n");
        }

        $output->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()), OutputInterface::OUTPUT_PLAIN);
    }
}
