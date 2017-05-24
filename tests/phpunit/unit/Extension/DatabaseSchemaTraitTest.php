<?php

namespace Bolt\Tests\Extension;

use Bolt\Storage\Database\Schema\Table\BaseTable;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\DatabaseSchemaExtension;

/**
 * Class to test Bolt\Extension\DatabaseSchemaTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseSchemaTraitTest extends BoltUnitTest
{
    public function testDatabaseSchemaExtension()
    {
        $app = $this->getApp();
        $ext = new DatabaseSchemaExtension();
        $ext->setContainer($app);
        $ext->register($app);
        $app->boot();
        $this->addToAssertionCount(1);
    }

    public function testRegisterTable()
    {
        $app = $this->getApp();
        $ext = new DatabaseSchemaExtension();
        $ext->setContainer($app);
        $ext->register($app);

        $extensionTableNames = $app['schema.extension_tables']->keys();
        $this->assertSame('round_table', reset($extensionTableNames));
        $table = $app['schema.extension_tables']['round_table'];
        $this->assertInstanceOf(BaseTable::class, $table);
        $this->assertInstanceOf(Mock\ExtensionTable::class, $table);
    }
}
