<?php

namespace Bolt\Extension;

use Bolt\Common\Deprecated;
use Pimple as Container;
use Symfony\Component\Console\Command\Command;

/**
 * Adding nut commands trait for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait NutTrait
{
    /**
     * Returns a list of nut commands to register.
     *
     * @param Container $container
     *
     * @return Command[]
     */
    protected function registerNutCommands(Container $container)
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendNutService()
    {
        $app = $this->getContainer();
        $app['nut.commands.add']($this->registerNutCommands($app));
    }

    /**
     * Add a console command.
     *
     * @param Command $command
     *
     * @deprecated since 3.0, will be removed in 4.0. Use registerNutCommands() instead.
     */
    protected function addConsoleCommand(Command $command)
    {
        Deprecated::method(3.0, 'registerNutCommands');

        $app = $this->getContainer();
        $app['nut.commands.add']($command);
    }

    /** @return Container */
    abstract protected function getContainer();
}
