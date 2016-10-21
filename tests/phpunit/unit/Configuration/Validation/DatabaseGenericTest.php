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
    public function testInvalidPlatform()
    {
        $databaseConfig = [
            'driver'   => 'pdo_koala',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('unsupported', null, 'pdo_koala')->shouldBeCalled();

        $this->validator->check('database');
    }

    public function testInvalidDbName()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('parameter', null, 'pdo_pgsql', 'databasename')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }

    public function testInvalidDbUser()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => 'koalas',
            'user'     => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('parameter', null, 'pdo_pgsql', 'username')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }

    public function testInvalidDbRootUser()
    {
        $databaseConfig = [
            'driver'   => 'pdo_pgsql',
            'dbname'   => 'koalas',
            'user'     => 'root',
            'password' => null,
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('insecure', null, 'pdo_pgsql')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }
}
