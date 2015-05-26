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
        $map = new MetadataDriver($app['integritychecker'], $app['config']->get('contenttypes'), $app['config']->get('taxonomies'), $app['storage.typemap']);
        $this->assertSame($app['integritychecker'], \PHPUnit_Framework_Assert::readAttribute($map, 'integrityChecker'));
        
    }
    
    public function testInitialize()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['integritychecker'], $app['config']->get('contenttypes'), $app['config']->get('taxonomies'), $app['storage.typemap']);
        $map->initialize();
        $metadata = $map->loadMetadataForClass('Bolt\Entity\Users');
        $this->assertNotNull($metadata);
        $this->assertEquals('bolt_users', $metadata->getTableName());
        $field = $metadata->getFieldMapping('id');
        $this->assertEquals('id', $field['fieldname']);
    }
    
}
