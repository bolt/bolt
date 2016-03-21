<?php

namespace Bolt\Extension;

use Pimple as Container;

/**
 * Storage helpers.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait StorageTrait
{
    /**
     * Return a list of entities to map to repositories.
     *
     * <pre>
     *  return [
     *      'alias' => [\Entity\Class\Name => \Repository\Class\Name],
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerRepositoryMappings()
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendRepositoryMapping()
    {
        $app = $this->getContainer();
        foreach ($this->registerRepositoryMappings() as $alias => $map) {
            $app['storage.repositories'] += $map;
            $app['storage.metadata']->setDefaultAlias($app['schema.prefix'] . $alias, key($map));
            $app['storage']->setRepository(key($map), current($map));
        }
    }

    /** @return Container */
    abstract protected function getContainer();
}
