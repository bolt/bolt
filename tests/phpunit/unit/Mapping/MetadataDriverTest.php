<?php
namespace Bolt\Tests\Mapping;

use Bolt\Tests\BoltUnitTest;
use Bolt\Mapping\MetadataDriver;

/**
 * Class to test src/Mapping/MetadataDriver.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class MetadataDriverTest extends BoltUnitTest
{
    
    public function testConstruct()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['integritychecker']);
        $this->assertSame($app['integritychecker'], \PHPUnit_Framework_Assert::readAttribute($map, 'integrityChecker'));
        
    }
    
    public function testInitialize()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['integritychecker']);
        $map->initialize();
    }
    
}
