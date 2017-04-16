<?php

namespace Bolt\Nut;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nut application.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NutApplication extends ConsoleApplication
{
    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }
        if (null === $output) {
            $output = new SymfonyStyle($input, new ConsoleOutput());
        }

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        // Get the Symfony defaults
        $commands = parent::getDefaultCommands();

        // Add command completion
        $commands[] = new CompletionCommand();

        return $commands;
    }
}
