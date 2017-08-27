<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * PostrgreSQL database validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabasePostgresTest extends AbstractValidationTest
{
    public function testPostgresExtensionLoaded()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
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
    public function testPostgresExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_pgsql',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }
}
