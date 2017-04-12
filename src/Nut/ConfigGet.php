<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this
            ->setName('config:get')
            ->setDescription('Get a parameter value from a YAML configuration file (default is config.yml)')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Specify config file to use', 'config://config.yml')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $file = $this->getFile($input);
        $yaml = new YamlUpdater($this->app, $file);

        try {
            $match = $yaml->get($key);
        } catch (\Exception $e) {
            $this->handleException($e, $file);

            return true;
        }

        if ($match === null) {
            $this->io->error(sprintf("The key '%s' was not found in %s.", $key, $file->getFilename()));

            return true;
        }

        if (is_bool($match)) {
            $match = $match ? 'true' : 'false';
        }
        $this->io->title(sprintf("Configuration setting in file %s", $file->getFilename()));
        $this->io->text([sprintf('%s: %s', $key, $match), '']);

        return false;
    }
}
