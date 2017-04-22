<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to get a parameter value from a YAML configuration file.
 */
class ConfigGet extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('config:get')
            ->setDescription('Get a value from a config file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(YamlUpdater $updater, InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');

        $value = $updater->get($key, true);
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $this->io->title(sprintf('Configuration setting in file %s', $this->file->getFullPath()));
        $this->io->text([sprintf('%s: %s', $key, $value), '']);
    }
}
