<?php

namespace Bolt\Tests\Storage;

use Bolt\Storage\Entity\Content;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Entity/Entity.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class EntityTest extends BoltUnitTest
{
    public function testCaseTransform()
    {
        $entity = new Content();
        $underscore = 'bolt_field_example';
        $camel = 'boltFieldExample';
        $camel2 = 'BoltFieldExample';

        $this->assertEquals('BoltFieldExample', $entity->camelize($underscore));
        $this->assertEquals('bolt_field_example', $entity->underscore($camel));
        $this->assertEquals('bolt_field_example', $entity->underscore($camel2));

    }
}
