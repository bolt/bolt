<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * Generic database validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseGenericTest extends AbstractValidationTest
{
    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\UnsupportedDatabaseException
     * @expectedExceptionMessage pdo_koala was selected as the database type, but it is not supported.
     */
    public function testInvalidPlatform()
    {
        $databaseConfig = [
            'driver'   => 'pdo_koala',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\DatabaseParameterException
     * @expectedExceptionMessage There is no 'databasename' set for your database.
     */
    public function testInvalidDbName()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\DatabaseParameterException
     * @expectedExceptionMessage There is no 'username' set for your database.
     */
    public function testInvalidDbUser()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => 'koalas',
            'user'     => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }

    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\Database\InsecureDatabaseException
     * @expectedExceptionMessage You're using the root user without a password. You are insecure.
     */
    public function testInvalidDbRootUser()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => 'koalas',
            'user'     => 'root',
            'password' => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->getDatabaseValidator()->check();
    }
}
