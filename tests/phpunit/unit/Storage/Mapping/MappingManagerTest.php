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
    public function testCaseTransform()
    {
        $app = $this->getApp();

        $manager = new MappingManager($app['mapping.definitions'], $app['mapping.default']);

        $this->assertEquals('Bolt\Storage\Mapping\Definition\Slug', $manager->getHandler('slug'));
    }
}
