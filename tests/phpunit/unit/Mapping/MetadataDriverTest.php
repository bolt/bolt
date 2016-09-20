<?php
namespace Bolt\Tests\Mapping;

use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Tests\BoltUnitTest;

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
        $map = new MetadataDriver($app['schema'], $app['storage.config.contenttypes'], $app['storage.config.taxonomy'], $app['storage.typemap']);
        $this->assertSame($app['schema'], \PHPUnit_Framework_Assert::readAttribute($map, 'schemaManager'));
    }

    public function testInitialize()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['schema'], $app['storage.config.contenttypes'], $app['storage.config.taxonomy'], $app['storage.typemap']);
        $map->initialize();
        $metadata = $map->loadMetadataForClass('Bolt\Storage\Entity\Users');
        $this->assertNotNull($metadata);
        $this->assertEquals('bolt_users', $metadata->getTableName());
        $field = $metadata->getFieldMapping('id');
        $this->assertEquals('id', $field['fieldname']);
    }
}
