<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * Apache validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ApacheTest extends AbstractValidationTest
{
    public function testApacheChecksValid()
    {
        $this->extensionController->systemCheck('htaccess')->shouldNotBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(true))
        ;

        $this->validator->check('apache');
    }

    public function testApacheChecksInvalid()
    {
        $this->extensionController->systemCheck('htaccess')->shouldBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(false));

        $this->validator->check('apache');
    }

    public function testApacheCheckCanBeDisabled()
    {
        $this->extensionController->systemCheck('htaccess')->shouldNotBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->never())
            ->method('is_readable');

        $this->validator->disableApacheChecks = true;

        $this->validator->check('apache');
    }
}
