<?php

namespace Bolt\Nut;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

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
    protected function getDefaultCommands()
    {
        // Get the Symfony defaults
        $commands = parent::getDefaultCommands();

        // Add command completion
        $commands[] = new CompletionCommand();

        return $commands;
    }
}
