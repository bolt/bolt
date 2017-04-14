<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this
            ->setName('config:set')
            ->setDescription('Set a parameter value in a YAML configuration file (config.yml by default)')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get')
            ->addArgument('value', InputArgument::REQUIRED, 'The value you wish to set it to')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Specify config file to use', 'config://config.yml')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Make a backup of the config file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');
        $backup = $input->getOption('backup');
        $file = $this->getFile($input);
        $updater = new YamlUpdater($file);

        try {
            $match = $updater->change($key, $value, $backup);
        } catch (\Exception $e) {
            $this->handleException($e, $file);

            return true;
        }

        if ($match === false) {
            $this->io->error(sprintf("The key '%s' was not found in %s.", $key, $file->getFilename()));

            return true;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->io->title(sprintf("Updating configuration setting in file %s", $file->getFilename()));
        $this->io->success([
            'Setting updated to:',
            sprintf('%s: %s', $key, $value),
        ]);
        $this->auditLog(__CLASS__, "Config value '$key: $value' set");

        return false;
    }
}
