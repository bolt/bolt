<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Bolt\Configuration\LowlevelChecks;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ConfigVerificationTest extends \PHPUnit_Framework_TestCase
{

    
    public function testInitWithVerifier()
    {
        $config = new Standard(getcwd());
        $verifier = new LowlevelChecks($config);
        $config->setVerifier($verifier);
        $config->verify();        
        
    }

   
}
