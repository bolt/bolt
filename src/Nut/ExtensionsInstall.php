<?php

namespace Bolt\Nut;

use Bolt\Composer\Satis\PingService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to install an extension.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsInstall extends BaseCommand
{
    /** @var PingService */
    private $pinger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:install')
            ->setAliases(['extensions:enable'])
            ->setDescription('Installs an extension by name and version.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to enable')
            ->addArgument('version', InputArgument::REQUIRED, 'Version of the extension to enable')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->pinger = $this->app['extend.ping'];
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->pinger->ping(true, $output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG)) {
            $this->io->error($this->pinger->getMessages()->toArray());

            return 1;
        }

        $name = $input->getArgument('name');
        $version = $input->getArgument('version');

        $this->io->title("Installing {$name}:{$version}");

        $result = $this->app['extend.manager']->requirePackage(['name' => $name, 'version' => $version]);
        $this->io->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()));
        if ($result === 0) {
            $this->io->success('Installation successful');
            $this->auditLog(__CLASS__, 'Autoloaders updated');
        } else {
            $this->io->error('Installation failed');
        }

        return $result;
    }
}
