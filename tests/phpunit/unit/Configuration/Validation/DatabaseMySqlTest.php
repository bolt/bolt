<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * MySQL database validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseMySqlTest extends AbstractValidationTest
{
    public function testMySqlExtensionLoaded()
    {
        $databaseConfig = [
            'driver'   => 'pdo_mysql',
            'dbname'   => 'koalas',
            'user'     => 'kenny',
            'password' => 'Dr0pb3@r',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
        $this->addToAssertionCount(1);
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\DatabaseParameterException
     * @expectedExceptionMessage There is no 'databasename' set for your database.
     */
    public function testMySqlExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_mysql',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }
}
