<?php

namespace Bolt\Tests\Storage;

use Bolt\Storage\Entity\Builder;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate.
 *
 * @covers \Bolt\Storage\Entity\Builder
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class EntityBuilderTest extends BoltUnitTest
{
    /**
     * @expectedException \Bolt\Exception\StorageException
     * @expectedExceptionMessage Attempted to load mapping data for unmapped class Bolt\Storage\Entity\Content
     */
    public function testBuilderCreateUnmapped()
    {
        $app = $this->getApp();
        /** @var Builder $builder */
        $builder = $app['storage.entity_builder'];

        $builder->create(['id' => 42, 'title' => 'Kenny drops the bear']);
    }
}
