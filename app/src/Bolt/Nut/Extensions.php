<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Extensions extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions')
            ->setDescription('Lists all installed extensions, and whether they\'re enabled or disabled.');
            //->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensions = $this->app['extensions']->getInfo();

        // print_r($extensions);

        $enabled = "";
        $disabled = "";
        $lines = array();

        foreach ($extensions as $key => $extension) {
            $line = sprintf("%-20s v. %-7s", $key, $extension['version']);
            if ($extension['enabled']) {
                $line .= "<info>[+]</info>\n";
                $enabled .= $line;
            } else {
                $line .= "[-]\n";
                $disabled .= $line;
            }
        }

        $lines[] = "\n<options=bold>Enabled Extensions</options=bold>";
        if (!empty($enabled)) {
            $lines[] = $enabled;
        } else {
            $lines[] = "(none)\n";
        }

        $lines[] = "<options=bold>Disabled Extensions</options=bold>";
        if (!empty($disabled)) {
            $lines[] = $disabled;
        } else {
            $lines[] = "(none)\n";
        }

        $output->writeln(implode("\n", $lines));
    }
}
