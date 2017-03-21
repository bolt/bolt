<?php

namespace Bolt\Tests\Configuration;

use Bolt\Configuration\LowlevelChecks;
use Bolt\Configuration\Standard;
use PHPUnit\Framework\TestCase;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigVerificationTest extends TestCase
{
    public function testInitWithVerifier()
    {
        $config = new Standard(getcwd());
        $verifier = new LowlevelChecks($config);
        $config->setVerifier($verifier);
        $config->verify();
    }
}
