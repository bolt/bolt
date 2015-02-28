<?php

namespace Bolt\Nut;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;

/**
 * Nut application.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NutApplication extends ConsoleApplication
{
    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
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
