<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to update extension autoloaders.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsDumpAutoload extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:dumpautoload')
            ->setDescription('Update the extensions autoloader.')
            ->setAliases(['extensions:dump-autoload'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Rebuilding extension autoloaders');

        $result = $this->app['extend.action']['autoload']->execute();
        $this->io->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()));
        if ($result === 0) {
            $this->io->success('Autoloaders updated');
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        } else {
            $this->io->error('Autoloaders failed update');
        }

        return $result;
    }
}
