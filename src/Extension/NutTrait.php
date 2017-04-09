<?php

namespace Bolt\Extension;

use Pimple\Container;
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

    /** @return Container */
    abstract protected function getContainer();
}
