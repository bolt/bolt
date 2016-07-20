<?php
namespace Bolt\Tests\Mapping;

use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Mapping/MetadataDriver.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class MetadataDriverTest extends BoltFunctionalTestCase
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['schema'], $app['config']->get('contenttypes'), $app['config']->get('taxonomy'), $app['storage.typemap']);
        $this->assertSame($app['schema'], \PHPUnit_Framework_Assert::readAttribute($map, 'schemaManager'));
    }

    public function testInitialize()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['schema'], $app['config']->get('contenttypes'), $app['config']->get('taxonomy'), $app['storage.typemap']);
        $map->initialize();
        $metadata = $map->loadMetadataForClass('Bolt\Storage\Entity\Users');
        $this->assertNotNull($metadata);
        $this->assertEquals('bolt_users', $metadata->getTableName());
        $field = $metadata->getFieldMapping('id');
        $this->assertEquals('id', $field['fieldname']);
    }
}
