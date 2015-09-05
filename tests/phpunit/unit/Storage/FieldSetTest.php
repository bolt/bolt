<?php
namespace Bolt\Tests\Storage;

use Bolt\Legacy\Storage;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldSetTest extends BoltUnitTest
{
    public function testSetWithNormalValues()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $entity = $repo->create(['title'=> "This is a title" ]);
        $this->assertEquals("This is a title", $entity->getTitle());
        $entity = $repo->create(['title'=> [1,2,3] ]);
        $this->assertEquals("1,2,3", $entity->getTitle());
    }

    
}
