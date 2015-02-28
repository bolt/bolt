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
        $installed = $this->app['extend.manager']->showPackage('installed');
        $rows = array();

        foreach ($installed as $ext) {
            /** @var \Composer\Package\CompletePackageInterface $package */
            $package = $ext['package'];
            $rows[] = array($package->getPrettyName(), $package->getPrettyVersion(), $package->getType(), $package->getDescription());
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Name', 'Version', 'Type',  'Description'))
            ->setRows($rows);
        $table->render($output);
    }
}
