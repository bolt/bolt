<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;

/**
 * Nut command to set parameter value in a YAML configuration file
 */
class ConfigSet extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('config:set')
            ->setDescription('Set a value in a config file')
            ->addArgument('value', InputArgument::REQUIRED, 'The value you wish to set it to')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Make a backup of the config file')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @param OutputStyle $output
     */
    protected function doExecute(YamlUpdater $updater, InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');
        $backup = $input->getOption('backup');

        $updater->change($key, $value, $backup, true);
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $output->title(sprintf("Updating configuration setting in file %s", $this->file->getFullPath()));
        $output->success([
            'Setting updated to:',
            sprintf('%s: %s', $key, $value),
        ]);
        $this->auditLog(__CLASS__, "Config value '$key: $value' set");
    }
}
