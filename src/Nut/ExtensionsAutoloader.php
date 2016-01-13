<?php
namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to update extension autoloaders.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsAutoloader extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:autoloader')
            ->setDescription('Update the extensions autoloader.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("\n<info>Updating autoloaders… </info>");

        $result = $this->app['extend.action']['autoload']->execute();
        if ($result === 0) {
            $output->write("<info>[DONE]</info>\n");
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        } else {
            $output->write("<error>[FAILED]</error>\n");
        }
        $output->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()));
    }
}
