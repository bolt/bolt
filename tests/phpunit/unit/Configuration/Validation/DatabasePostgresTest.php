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
        $this->extensionController->databaseDriver('missing', 'PostgreSQL', 'pdo_pgsql')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }

    public function testPostgresExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_pgsql',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('missing', 'PostgreSQL', 'pdo_pgsql')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(false))
        ;

        $this->validator->check('database');
    }
}
