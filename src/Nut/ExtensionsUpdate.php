<?php

namespace Bolt\Nut;

use Bolt\Composer\Satis\PingService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to update extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsUpdate extends BaseCommand
{
    /** @var PingService */
    private $pinger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:update')
            ->setDescription('Updates extension(s).')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the extension to update')
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
        if ($name) {
            $this->io->title('Updating $name');
            $packages = [$name];
        } else {
            $this->io->title('Updating all extensions');
            $packages = [];
        }
        $result = $this->app['extend.manager']->updatePackage($packages);
        $this->io->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()), OutputInterface::OUTPUT_PLAIN);
        if ($result === 0) {
            $this->io->success("Updated extension $name");
            $this->auditLog(__CLASS__, "Updated extension $name");
        } else {
            $this->io->error("Unable to update extension $name");
        }

        return $result;
    }
}
