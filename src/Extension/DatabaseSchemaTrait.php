<?php

namespace Bolt\Extension;

use Bolt\Storage\Database\Schema\Table\BaseTable;
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
     * @return BaseTable[]
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
                function (\Pimple $tables) use ($app) {
                    foreach ((array) $this->registerExtensionTables() as $baseName => $table) {
                        $tables[$baseName] = $table;
                    }

                    return $tables;
                }
            )
        );
    }

    /** @return Container */
    abstract protected function getContainer();
}
