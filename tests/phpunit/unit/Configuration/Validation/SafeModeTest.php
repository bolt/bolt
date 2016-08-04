<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;

/**
 * Safe mode validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SafeModeTest extends AbstractValidationTest
{
    public function testSafeModeEnabled()
    {
        $this->extensionController->systemCheck(Validator::CHECK_SAFE_MODE)->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('ini_get')
            ->will($this->returnValue(true));

        $this->validator->check(Validator::CHECK_SAFE_MODE);
    }

    public function testSafeModeDisabled()
    {
        $this->extensionController->systemCheck(Validator::CHECK_SAFE_MODE)->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('ini_get')
            ->will($this->returnValue(false));

        $this->validator->check(Validator::CHECK_SAFE_MODE);
    }
}
