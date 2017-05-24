<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to run all available PHPUnit tests.
 */
class TestRunner extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tests:run')
            ->setDescription('Runs all available tests')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Running PHPUnit Tests');
        $executable = null;
        if (is_file(dirname(dirname(__DIR__)) . '/vendor/bin/phpunit')) {
            // development install
            $executable = realpath(dirname(dirname(__DIR__)) . '/vendor/bin/phpunit');
        } elseif (is_file(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/bin/phpunit')) {
            // composer install
            $executable = realpath(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/bin/phpunit');
        } elseif (is_file(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/phpunit/phpunit/phpunit.php')) {
            // perhaps no shortcut was made?
            $executable = 'php ' . realpath(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/phpunit/phpunit/phpunit.php');
        } else {
            // check if phpunit is in the path
            $returnVal = shell_exec('which phpunit');
            if (!empty($returnVal)) {
                $executable = 'phpunit';
            }
        }

        if ($executable === null) {
            $this->io->error('No PHPUnit test runner found in the vendor dir or your path');

            return 1;
        }
        $result = system($executable);

        return $result ? 1 : 0;
    }
}
