<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGet extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a value from config.yml.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $file = BOLT_CONFIG_DIR . "/config.yml";

        $yaml = new \Bolt\YamlUpdater($file);
        $match = $yaml->get($key);

        if (!empty($match)) {
            $result = sprintf("%s: %s", $key, $match['value']);
        } else {
            $result = sprintf("%s not found.", $key);
        }

        $output->writeln($result);
    }
}
