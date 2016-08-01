<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;

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
        $this->extensionController->systemCheck(Validator::CHECK_APACHE)->shouldNotBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(true))
        ;

        $this->validator->check(Validator::CHECK_APACHE);
    }

    public function testApacheChecksInvalid()
    {
        $this->extensionController->systemCheck(Validator::CHECK_APACHE)->shouldBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(false));

        $this->validator->check(Validator::CHECK_APACHE);
    }

    public function testApacheCheckCanBeDisabled()
    {
        $this->extensionController->systemCheck(Validator::CHECK_APACHE)->shouldNotBeCalled();

        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->never())
            ->method('is_readable');

        $this->validator->disableApacheChecks = true;

        $this->validator->check(Validator::CHECK_APACHE);
    }
}
