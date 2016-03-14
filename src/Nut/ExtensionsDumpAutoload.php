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
        $output->write("\n<info>Rebuilding autoloaders… </info>");

        $result = $this->app['extend.action']['autoload']->execute();
        if ($result === 0) {
            $output->writeln('<info>[DONE]</info>');
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        } else {
            $output->writeln('<error>[FAILED]</error>');
        }
        $output->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()));
    }
}
