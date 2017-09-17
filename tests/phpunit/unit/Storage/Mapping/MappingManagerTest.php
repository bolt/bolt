<?php

namespace Bolt\Tests\Storage\Mapping;

use Bolt\Storage\Mapping\MappingManager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Mapping/MappingManager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class MappingManagerTest extends BoltUnitTest
{
    public function testLookup()
    {
        $app = $this->getApp();
        $manager = $app['mapping'];

        $this->assertEquals('slug', $manager->getHandler('slug'));
        $this->assertEquals('file', $manager->getHandler('filelist'));
        $this->assertEquals('Bolt\Storage\Mapping\Definition', $manager->getHandler('nonexistent'));
    }

    public function testLoad()
    {
        $app = $this->getApp();
        $manager = $app['mapping'];

        $this->assertInstanceOf('Bolt\Storage\Mapping\Definition\Slug', $manager->load('slug', ['type' => 'slug']));
        $this->assertInstanceOf('Bolt\Storage\Mapping\Definition', $manager->load('title', ['type' => 'text']));
    }
}
