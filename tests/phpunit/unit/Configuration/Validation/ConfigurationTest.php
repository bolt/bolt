<?php

namespace Bolt\Tests\Configuration\Validation;

use Bolt\Configuration\Validation\Validator;

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
        $this->extensionController->systemCheck(Validator::CHECK_CONFIG)->shouldNotBeCalled();

        $this->validator->check(Validator::CHECK_CONFIG);
    }

    public function testConfigurationInvalid()
    {
        $this->config->getExceptions()->willReturn(['Koala detected … check for drop bear!']);
        $this->flashLogger->error('Koala detected … check for drop bear!')->shouldBeCalled();

        $this->validator->check(Validator::CHECK_CONFIG);
    }
}
