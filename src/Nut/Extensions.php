<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to list all installed extensions
 */
class Extensions extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('extensions')
            ->setDescription('Lists all installed extensions');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (count($this->app['extend.manager']->getMessages())) {
            foreach ($this->app['extend.manager']->getMessages() as $message) {
                $output->writeln(sprintf('<error>%s</error>', $message));
            }
            return;
        }

        $allPackages = $this->app['extend.manager']->getAllPackages();
        $rows = [];

        foreach ($allPackages as $type => $packages) {
            foreach ($packages as $package) {
                $rows[] = [$package['name'], $package['version'], $package['type'], $package['descrip']];
            }
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(['Name', 'Version', 'Type',  'Description'])
            ->setRows($rows);
        $table->render($output);
    }
}
