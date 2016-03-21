<?php

namespace Bolt\Extension;

use Pimple as Container;

/**
 * Database schema modification.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait DatabaseSchemaTrait
{
    /**
     * Return a set of tables to register.
     *
     * <pre>
     *  return [
     *      'table_name' => \Fully\Qualified\Table\ClassName::class,
     *  ];
     * </pre>
     *
     * @return string[]
     */
    protected function registerExtensionTables()
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendDatabaseSchemaServices()
    {
        $app = $this->getContainer();

        $app['schema.extension_tables'] = $app->share(
            $app->extend(
                'schema.extension_tables',
                function (Container $tables) use ($app) {
                    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                    $platform = $app['db']->getDatabasePlatform();
                    $prefix = $app['schema.prefix'];

                    foreach ((array) $this->registerExtensionTables() as $baseName => $table) {
                        $tables[$baseName] = new $table($platform, $prefix);
                    }

                    return $tables;
                }
            )
        );
    }

    /** @return Container */
    abstract protected function getContainer();
}
