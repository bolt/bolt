<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;

/**
 * Magic quotes validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MagicQuotesTest extends AbstractValidationTest
{
    public function testMagicQuotesEnabled()
    {
        $this->extensionController->systemCheck(Validator::CHECK_MAGIC_QUOTES)->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(true));

        $this->validator->check(Validator::CHECK_MAGIC_QUOTES);
    }

    public function testMagicQuotesDisabled()
    {
        $this->extensionController->systemCheck(Validator::CHECK_MAGIC_QUOTES)->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(false));

        $this->validator->check(Validator::CHECK_MAGIC_QUOTES);
    }
}
