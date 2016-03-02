<?php
namespace Bolt\Tests\Storage\Repository;

use Bolt\Storage\Entity\Content;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Repository/Content
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentRepositoryTest extends BoltUnitTest
{
    public function testConstruct()
    {
        $this->resetDb();
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('bolt_showcases');

        $this->assertInstanceOf('Bolt\Storage\Repository\ContentRepository', $repo);
    }

    public function testCreate()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $showcase = new Content([
            'title'  => 'Test Showcase',
            'slug'   => 'test-showcase',
            'status' => 'published',
        ]);
        $res = $repo->save($showcase);
        $this->assertNotEmpty($res);
    }

    public function testFind()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $record = $repo->find(1);
        $this->assertEquals('Test Showcase', $record->title);
        $this->assertEquals('test-showcase', $record->slug);
        $this->assertEquals('published', $record->status);
    }

    public function testUpdate()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $record = $repo->find(1);
        $record->title = 'Updated Test Showcase';
        $repo->save($record);

        $record2 = $repo->find(1);
        $this->assertEquals('Updated Test Showcase', $record2->title);
        $this->assertEquals('test-showcase', $record2->slug);
        $this->assertEquals('published', $record2->status);
    }

    public function testDelete()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $record = $repo->find(1);
        $res = $repo->delete($record);

        $this->assertEquals(1, $res);
        $record2 = $repo->find(1);

        $this->assertFalse($record2);
    }

    public function testFactoryCreate()
    {
        $app = $this->getApp();
        $em = $app['storage'];

        $repo = $em->getRepository('showcases');
        $record = $repo->create([
            'title'  => 'New Test Showcase',
            'slug'   => 'new-test-showcase',
            'status' => 'published',
        ]);

        $this->assertInstanceOf('Bolt\Storage\Entity\Content', $record);
    }
}
