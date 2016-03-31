<?php

namespace Bolt\Extension;

use Bolt\Helpers\Arr;
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
     *      'alias' => [\Entity\Class::class => \Repository\Class::class],
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

        $app['storage'] = $app->share(
            $app->extend(
                'storage',
                function ($entityManager) use ($app) {
                    foreach ($this->registerRepositoryMappings() as $alias => $map) {
                        if (Arr::isIndexedArray($map)) {
                            // Usually caused by [entity, repo] instead of [entity => repo]
                            throw new \RuntimeException(sprintf('Repository mapping for %s `%s` is not an associative array.', __CLASS__, $alias));
                        }
                        $app['storage.repositories'] += $map;
                        $app['storage.metadata']->setDefaultAlias($app['schema.prefix'] . $alias, key($map));
                        $entityManager->setRepository(key($map), current($map));
                    }

                    return $entityManager;
                }
            )
        );
    }

    /** @return Container */
    abstract protected function getContainer();
}
