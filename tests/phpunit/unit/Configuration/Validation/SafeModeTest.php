<?php

namespace Bolt\Tests\Configuration\Validation;

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
        $this->extensionController->systemCheck('safe-mode')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('ini_get')
            ->will($this->returnValue(true));

        $this->validator->check('safeMode');
    }

    public function testSafeModeDisabled()
    {
        $this->extensionController->systemCheck('safe-mode')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('ini_get')
            ->will($this->returnValue(false));

        $this->validator->check('safeMode');
    }
}
