<?php
namespace Bolt\Tests\Logger;

use Bolt\Logger\ChangeLog;
use Bolt\Logger\ChangeLogItem;
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
    public function testSetup()
    {
        $app = $this->getApp();
    }

    public function testGetChangelog()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);

        $content = $storage->getContentObject('pages');
        $storage->saveContent($content, 'pages');
        $logs = $app['logger.manager.change']->getChangeLog(array('limit' => 1, 'offset' => 0, 'order' => 'id'));
        $logs2 = $app['logger.manager.change']->getChangeLog(array('limit' => 1));
        $this->assertEquals(1, count($logs));
        $this->assertEquals(1, count($logs2));
    }

    public function testCountChangelog()
    {
        $app = $this->getApp();
        $count = $app['logger.manager.change']->countChangelog();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testGetChangelogByContentType()
    {
        $app = $this->getApp();
        $log = $app['logger.manager.change']->getChangeLogByContentType('pages', array('limit' => 1, 'offset' => 0, 'order' => 'id'));
        $this->assertEquals(1, count($log));
    }

    public function testGetChangelogByContentTypeArray()
    {
        $app = $this->getApp();
        $log = $app['logger.manager.change']->getChangeLogByContentType(array('slug' => 'pages'), array('limit' => 1, 'contentid' => 1));
        $this->assertEquals(1, count($log));
    }

    public function testCountChangelogByContentType()
    {
        $app = $this->getApp();
        $count = $app['logger.manager.change']->countChangelogByContentType('pages', array());
        $this->assertGreaterThan(0, $count);

        $count = $app['logger.manager.change']->countChangelogByContentType('pages', array('contentid' => 1));
        $this->assertGreaterThan(0, $count);

        $count = $app['logger.manager.change']->countChangelogByContentType(array('slug' => 'pages'), array('id' => 1));
        $this->assertGreaterThan(0, $count);
    }

    public function testGetChangelogEntry()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        //$all = $app['logger.manager.change']->getChangeLogByContentType('pages', array());

        $log = $app['logger.manager.change']->getChangeLogEntry('pages', 1, 1);
        $this->assertInstanceOf('Bolt\Logger\ChangeLogItem', $log);
        $this->assertAttributeEquals(1, 'contentid', $log);
    }

    public function testGetNextChangelogEntry()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);

        // To generate an extra changelog we fetch and save a content item
        // For now we need to mock the request object.
        $app['request'] = Request::create('/');
        $content = $storage->getContent('pages/1');
        $this->assertInstanceOf('\Bolt\Content', $content);

        $content->setValues(array('status' => 'draft', 'ownerid' => 99));
        $storage->saveContent($content, 'Test Suite Update');
        $content->setValues(array('status' => 'published', 'ownerid' => 1));
        $storage->saveContent($content, 'Test Suite Update');

        $log = $app['logger.manager.change']->getNextChangelogEntry('pages', 1, 1);
        $this->assertInstanceOf('Bolt\Logger\ChangeLogItem', $log);
        $this->assertAttributeEquals(1, 'contentid', $log);
    }

    public function testGetPrevChangelogEntry()
    {
        $app = $this->getApp();
        $log = $app['logger.manager.change']->getPrevChangelogEntry('pages', 1, 10);
        $this->assertInstanceOf('Bolt\Logger\ChangeLogItem', $log);
        $this->assertAttributeEquals(1, 'contentid', $log);
    }
}
