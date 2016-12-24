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

    public function testEntityUpdatePartial()
    {
        $app = $this->getApp();
        $repo = $app['storage']->getRepository('pages');

        $entity = $repo->getEntityBuilder()->getEntity();
        $entity->set('title', 'Kenny Koala');
        $entity->set('status', 'published');
        $entity->set('slug', 'kenny-koala');
        $entity->set('image', ['file' => 'koala.png']);
        $repo->save($entity);
        $id = $entity->getId();

        $entity = $repo->getEntityBuilder()->getEntity();
        $entity->set('id', $id);
        $entity->set('title', 'Kenny Koala Jr.');
        $entity->set('status', 'draft');
        $entity->set('slug', 'kenny-koala');
        $repo->save($entity);

        $entity = $repo->find($id);
        $this->assertSame($entity->getTitle(), 'Kenny Koala Jr.');
        $this->assertSame($entity->getStatus(), 'draft');
        $this->assertSame($entity->getSlug(), 'kenny-koala');
        $this->assertSame($entity->getImage(), ['file' => 'koala.png']);
    }
}
