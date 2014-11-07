<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Composer\Autoload\ClassLoader;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StandardConfigurationTest extends \PHPUnit_Framework_TestCase
{

    
    public function testInitWithClassloader()
    {
        $loader = require __DIR__."/../../vendor/autoload.php";
        $config = new Standard($loader);
        $app = new Application(array('resources' => $config));
        $this->assertEquals('/app/', $config->getUrl('app'));
    }

   
}
