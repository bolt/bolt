<?php

namespace Bolt\Tests\Configuration\Validation;

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
        $this->extensionController->systemCheck('magic-quotes')->shouldBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(true));

        $this->validator->check('magicQuotes');
    }

    public function testMagicQuotesDisabled()
    {
        $this->extensionController->systemCheck('magic-quotes')->shouldNotBeCalled();

        $this->_validation
            ->expects($this->once())
            ->method('get_magic_quotes_gpc')
            ->will($this->returnValue(false));

        $this->validator->check('magicQuotes');
    }
}
