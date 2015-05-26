<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Standard;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StandardConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testInitWithClassloader()
    {
        $loader = require BOLT_AUTOLOAD;
        $config = new Standard($loader);
        $app = new Application(['resources' => $config]);
        $this->assertEquals('/app/', $config->getUrl('app'));
    }
}
