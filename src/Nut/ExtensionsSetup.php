<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to set up extension directories.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsSetup extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:setup')
            ->setDescription('Set up extension directories, and create/update composer.json.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupJson();
        $this->setupAutoloader();
    }

    /**
     * Create or update the extensions/composer.json file.
     */
    private function setupJson()
    {
        $this->io->title('Creating/updating composer.json');
        $this->app['extend.manager.json']->update();
        $this->io->success('Success');
    }

    /**
     * Set up the Composer autoloader.
     *
     * @throws \Bolt\Exception\PackageManagerException
     */
    private function setupAutoloader()
    {
        $this->io->title('Updating autoloaders');
        $result = $this->app['extend.action']['autoload']->execute();
        $this->outputResult($result);
    }

    /**
     * Output the relevant result.
     *
     * @param int $result
     *
     * @return int
     */
    private function outputResult($result)
    {
        $this->io->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()), OutputInterface::OUTPUT_PLAIN);

        if ($result === 0) {
            $this->io->success('Autoloaders updated');
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        } else {
            $this->io->error('Autoloaders failed update');
        }

        return $result;
    }
}
