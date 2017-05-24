<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Silex\Application;

/**
 *  Mock extension that extends SimpleExtension for testing the DatabaseSchemaTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseSchemaExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();
    }

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return ['round_table' => ExtensionTable::class];
    }
}
