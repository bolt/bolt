<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to list all installed extensions.
 */
class Extensions extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions')
            ->setDescription('Lists all installed extensions')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (count($this->app['extend.manager']->getMessages())) {
            foreach ($this->app['extend.manager']->getMessages() as $message) {
                $output->writeln(sprintf('<error>%s</error>', $message));
            }

            return;
        }

        $installed = $this->app['extend.manager']->showPackage('installed');
        $rows = [];

        foreach ($installed as $ext) {
            /** @var \Composer\Package\CompletePackageInterface $package */
            $package = $ext['package'];
            $rows[] = [$package->getPrettyName(), $package->getPrettyVersion(), $package->getType(), $package->getDescription()];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Version', 'Type',  'Description'])
            ->setRows($rows)
            ->render()
        ;
    }
}
