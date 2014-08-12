<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionsEnable extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions:enable')
            ->setDescription('Enables an extension.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the extension to enable');
            //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $extensions = $this->app['extensions']->getInfo();

        $enabled = array();
        $lines = array();
        $update = false;

        foreach ($extensions as $key => $extension) {
            if ($extension['enabled']) {
                $enabled[] = $key;
            }

            if (strtolower($key) == strtolower($name)) {
                if (!in_array($key, $enabled)) {
                    $lines[] = "<info>Enabling <options=bold>$key</options=bold>.</info>";
                    $update = true;
                    $enabled[] = $key;
                } else {
                    $lines[] = "<info><options=bold>$key</options=bold> is already enabled.</info>";
                }
            }

        }

        if ($update) {
            $key = "enabled_extensions";
            $file = BOLT_CONFIG_DIR . "/config.yml";
            $yaml = new \Bolt\YamlUpdater($file);
            $result = $yaml->change($key, $enabled);

            if ($result) {
                $lines[] = sprintf("New value for <info>%s</info> was succesful. File updated.", $key);
            } else {
                $lines[] = sprintf("<error>%s not found, or file not writable.</error>", $key);
            }

        }

        $output->writeln(implode("\n", $lines));
    }
}
