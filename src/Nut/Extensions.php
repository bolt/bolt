<?php

namespace Bolt\Nut;

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
        $messages = $this->app['extend.manager']->getMessages();
        if (count($messages)) {
            $this->io->error($messages);

            return 1;
        }

        $rows = [];
        $installed = $this->app['extend.manager']->showPackage('installed');
        if (empty($installed)) {
            $this->io->note('No extensions installed');

            return 0;
        }

        $this->io->title('Installed extensions');
        foreach ($installed as $ext) {
            /** @var \Composer\Package\CompletePackageInterface $package */
            $package = $ext['package'];
            $rows[] = [$package->getPrettyName(), $package->getPrettyVersion(), $package->getType(), $package->getDescription()];
        }
        $this->io->table(['Name', 'Version', 'Type',  'Description'], $rows);

        return 0;
    }
}
