<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to update extensions
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsUpdate extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:update')
            ->setDescription('Updates extension(s).')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the extension to update')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        if ($name) {
            $output->write("\n<info>Starting update of {$name}:… </info>");
            $packages = [$name];
        } else {
            $output->write("\n<info>Starting update… </info>");
            $packages = [];
        }
        $result = $this->app['extend.manager']->updatePackage($packages);

        if ($result === 0) {
            $output->writeln('<info>[DONE]</info>');
            $this->auditLog(__CLASS__, "Update extension $name");
        } else {
            $output->writeln('<error>[FAILED]</error>');
        }

        $output->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()), OutputInterface::OUTPUT_PLAIN);
    }
}
