<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigSet extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a value in config.yml.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get.')
            ->addArgument('value', InputArgument::REQUIRED, 'The value you wish to set it to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');
        $file = BOLT_CONFIG_DIR . "/config.yml";

        $yaml = new \Bolt\YamlUpdater($file);
        $result = $yaml->change($key, $value);

        if ($result) {
            $result = sprintf("New value for <info>%s: %s</info> was succesful. File updated.", $key, $value);
        } else {
            $result = sprintf("<error>%s not found, or file not writable.</error>", $key);
        }

        $output->writeln($result);
    }
}
