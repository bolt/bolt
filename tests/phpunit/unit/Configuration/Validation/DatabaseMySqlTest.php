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
        $this->extensionController->databaseDriver('missing', 'MySQL', 'pdo_mysql')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('database');
    }

    public function testMySqlExtensionNotLoaded()
    {
        $databaseConfig = [
            'driver' => 'pdo_mysql',
        ];
        $this->config->get('general/database')->willReturn($databaseConfig);
        $this->extensionController->databaseDriver('missing', 'MySQL', 'pdo_mysql')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('extension_loaded')
            ->will($this->returnValue(false))
        ;

        $this->validator->check('database');
    }
}
