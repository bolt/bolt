<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;
use Bolt\Exception\Configuration\Validation\System\ApacheValidationException;

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
        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(true))
        ;

        $this->validator->check(Validator::CHECK_APACHE);
    }

    /**
     * @expectedException ApacheValidationException
     */
    public function testApacheChecksInvalid()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->once())
            ->method('is_readable')
            ->will($this->returnValue(false));

        $this->validator->check(Validator::CHECK_APACHE);
    }

    public function testApacheCheckCanBeDisabled()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache 1.0';

        $this->_validation
            ->expects($this->never())
            ->method('is_readable');

        $this->validator->disableApacheChecks = true;

        $this->validator->check(Validator::CHECK_APACHE);
    }
}
