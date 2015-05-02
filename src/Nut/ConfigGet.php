<?php

namespace Bolt\Nut;

use Bolt\Exception\FilesystemException;
use Bolt\YamlUpdater;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Nut command to get a value in config.yml
 */
class ConfigGet extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a value from config.yml.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get.')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, "Specify config file to use");
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');

        if ($input->getOption('file')) {
            $file = $input->getOption('file');
        } else {
            $file = 'config.yml';
        }

        try {
            $yaml = new YamlUpdater($this->app, $file);
            $match = $yaml->get($key);

            if (!empty($match)) {
                $result = sprintf("%s: %s", $key, $match['value']);
            } else {
                $result = sprintf("<error>The key '%s' was not found in %s.</error>", $key, $file);
            }
        } catch (FileNotFoundException $e) {
            $result = sprintf("<error>Can't read file: %s.</error>", $file);
        } catch (ParseException $e) {
            $result = sprintf("<error>Invalid YAML in file: %s.</error>", $file);
        } catch (FilesystemException $e) {
            $result = sprintf('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln($result);
    }
}
