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
    /**
     * @expectedException \Bolt\Exception\Configuration\Validation\System\MagicQuotesValidationException
     */
    public function testMagicQuotesEnabled()
    {
        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(true));

        $this->validator->check(Validator::CHECK_MAGIC_QUOTES);
    }

    public function testMagicQuotesDisabled()
    {
        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(false));

        $this->validator->check(Validator::CHECK_MAGIC_QUOTES);
    }
}
