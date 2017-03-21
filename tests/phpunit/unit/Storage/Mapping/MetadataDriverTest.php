<?php

namespace Bolt\Tests\Storage\Mapping;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Tests\BoltUnitTest;
use PHPUnit\Framework\Assert;

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
        $this->assertSame($app['schema'], Assert::readAttribute($map, 'schemaManager'));
    }

    public function testInitialize()
    {
        $app = $this->getApp();
        $map = new MetadataDriver($app['schema'], $app['storage.config.contenttypes'], $app['storage.config.taxonomy'], $app['storage.typemap'], $app['storage.namingstrategy']);
        $map->initialize();
        $metadata = $map->loadMetadataForClass(Entity\Users::class);
        $this->assertNotNull($metadata);
        $this->assertEquals('bolt_users', $metadata->getTableName());
        $field = $metadata->getFieldMapping('id');
        $this->assertEquals('id', $field['fieldname']);
    }
}
