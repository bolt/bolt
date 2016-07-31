<?php

namespace Bolt\Tests\Configuration\Validation;

/**
 * Configuration parameters validation tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigurationTest extends AbstractValidationTest
{
    public function testConfigurationValid()
    {
        $this->config->getExceptions()->willReturn(null);
        $this->extensionController->systemCheck('config-parameters')->shouldNotBeCalled();

        $this->validator->check('configuration');
    }

    public function testConfigurationInvalid()
    {
        $this->config->getExceptions()->willReturn(['Koala detected … check for drop bear!']);
        $this->extensionController->systemCheck('config-parameters', ['Koala detected … check for drop bear!'])->shouldBeCalled();

        $this->validator->check('configuration');
    }
}
