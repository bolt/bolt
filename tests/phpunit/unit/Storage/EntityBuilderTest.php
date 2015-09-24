<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class EntityBuilderTest extends BoltUnitTest
{
    public function testBuilderCreate()
    {
        $app = $this->getApp();
        $builder = $app['storage.entity_builder'];
    }
}
