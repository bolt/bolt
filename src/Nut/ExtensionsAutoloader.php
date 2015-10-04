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
            ->setDescription('Update the extensions autoloader.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['extensions']->checkLocalAutoloader(true);

        if ($result === 0) {
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        }

        $output->writeln("\n<info>[Done] Autoloaders updated</info>\n");
        $output->writeln($result, OutputInterface::OUTPUT_PLAIN);
    }
}
