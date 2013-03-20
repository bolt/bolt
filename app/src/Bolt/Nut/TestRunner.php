<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRunner extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('tests:run')
            ->setDescription('Runs all available tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check for composer installed
        $executable = null;

        if (is_file(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/bin/phpunit')){
            $executable = realpath(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/bin/phpunit');
        } elseif (is_file(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/phpunit/phpunit/phpunit.php')){
            // perhaps no shortcut was made?
            $executable = 'php ' . realpath(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/phpunit/phpunit/phpunit.php');
        } else {
            // check if phpunit is in the path
            $returnVal = shell_exec("which phpunit");
            if (!empty($returnVal)){
                $executable = 'phpunit';
            }
        }

        if (is_null($executable)){
            $output->writeln("No PHPUnit test runner found in the vendor dir or your path");
        }
        else {
            system($executable . ' -c app/');
        }
    }
}
