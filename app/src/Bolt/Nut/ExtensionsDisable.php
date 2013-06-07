<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionsDisable extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions:disable')
            ->setDescription('Disables an extension.')
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

        foreach($extensions as $key => $extension) {
            if ($extension['enabled']) {
                if (strtolower($key)==strtolower($name)) {
                    $lines[] = "<info>Disabling <options=bold>$key</options=bold>.</info>";
                    $update = true;
                } else {
                    $enabled[] = $key;
                }
            }

        }

        if ($update) {
            $key = "enabled_extensions";
            $file = $this->app['paths']['apppath']."/config/config.yml";
            $yaml = new \Bolt\YamlUpdater($file);
            $result = $yaml->change($key, $enabled);

            if ($result) {
                $lines[] = sprintf("New value for <info>%s</info> was succesful. File updated.", $key);
            } else {
                $lines[] = sprintf("<error>%s not found, or file not writable.</error>", $key);
            }

        } else {
            "<info><options=bold>$name</options=bold> is already disabled.</info>";
        }

        $output->writeln(implode("\n", $lines));
    }
}
