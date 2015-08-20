<?php
namespace Bolt\Tests\Logger;

use Bolt\Storage;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Logger/ChangeLog.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ChangeLogTest extends BoltUnitTest
{
    public function setUp()
    {
        $this->resetDb();
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $this->addSomeContent();
        $storage = new Storage($app);

        $content = $storage->getContentObject('pages');
        $content['contentid'] = 1;
        $storage->saveContent($content, 'pages');
    }

    public function testGetChangeLog()
    {
        $logs = $this->getLogChangeRepository()->getChangeLog(['limit' => 1, 'offset' => 0, 'order' => 'id']);
        $logs2 = $this->getLogChangeRepository()->getChangeLog(['limit' => 1]);
        $this->assertEquals(1, count($logs));
        $this->assertEquals(1, count($logs2));
    }

    public function testCountChangeLog()
    {
        $count = $this->getLogChangeRepository()->countChangeLog();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testGetChangeLogByContentType()
    {
        $log = $this->getLogChangeRepository()->getChangeLogByContentType('pages', ['limit' => 1, 'offset' => 0, 'order' => 'id']);
        $this->assertEquals(1, count($log));
    }

    public function testGetChangeLogByContentTypeArray()
    {
        $log = $this->getLogChangeRepository()->getChangeLogByContentType('pages', ['limit' => 1, 'contentid' => 1]);
        $this->assertEquals(1, count($log));
    }

    public function testCountChangeLogByContentType()
    {
        $count = $this->getLogChangeRepository()->countChangeLogByContentType('pages', []);
        $this->assertGreaterThan(0, $count);

        $count = $this->getLogChangeRepository()->countChangeLogByContentType('pages', ['contentid' => 1]);
        $this->assertGreaterThan(0, $count);
        $count = $this->getLogChangeRepository()->countChangeLogByContentType('pages', ['ownerid' => 1]);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetChangeLogEntry()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        //$all = $this->getLogChangeRepository()->getChangeLogByContentType('pages', []);

        $log = $this->getLogChangeRepository()->getChangeLogEntry('pages', 1, 1, '=');
        $this->assertInstanceOf('\Bolt\Storage\Entity\LogChange', $log);
    }

    public function testGetNextChangeLogEntry()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);

        // To generate an extra changelog we fetch and save a content item
        // For now we need to mock the request object.
        $app['request'] = Request::create('/');
        $content = $storage->getContent('pages/1');
        $this->assertInstanceOf('\Bolt\Legacy\Content', $content);

        $content->setValues(['status' => 'draft', 'ownerid' => 99]);
        $storage->saveContent($content, 'Test Suite Update');
        $content->setValues(['status' => 'published', 'ownerid' => 1]);
        $storage->saveContent($content, 'Test Suite Update');

        $log = $this->getLogChangeRepository()->getChangeLogEntry('pages', 1, 1, '>');
        $this->assertAttributeEquals(1, 'contentid', $log);
    }

    public function testGetPrevChangeLogEntry()
    {
        $log = $this->getLogChangeRepository()->getChangeLogEntry('pages', 1, 10, '<');
        $this->assertAttributeEquals(1, 'contentid', $log);
    }

    /**
     * @return \Bolt\Storage\Repository\LogChangeRepository
     */
    protected function getLogChangeRepository()
    {
        $app = $this->getApp();

        return $app['storage']->getRepository('Bolt\Storage\Entity\LogChange');
    }
}
