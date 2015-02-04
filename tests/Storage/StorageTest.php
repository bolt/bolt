<?php
namespace Bolt\tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage;
use Bolt\Content;
use Bolt\Events\StorageEvents;
use Bolt\Exception\StorageException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StorageTest extends BoltUnitTest
{
    public function testGetContentObject()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $content = $storage->getContentObject('pages');
        $this->assertInstanceOf('Bolt\Content', $content);

        $fields = $app['config']->get('contenttypes/pages/fields');

        $mock = $this->getMock('Bolt\Content', null, array($app), 'Pages');
        $content = $storage->getContentObject(array('class' => 'Pages', 'fields' => $fields));
        $this->assertInstanceOf('Pages', $content);
        $this->assertInstanceOf('Bolt\Content', $content);

        // Test that a class not instanceof Bolt\Content fails
        $mock = $this->getMock('stdClass', null, array(), 'Failing');
        $this->setExpectedException('Exception', 'Failing does not extend \Bolt\Content.');
        $content = $storage->getContentObject(array('class' => 'Failing', 'fields' => $fields));
    }

    public function testPreFill()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);
        $output = $storage->prefill(array('showcases'));
        $this->assertRegExp('#Added#', $output);
        $this->assertRegExp('#Done#', $output);

        $output = $storage->prefill();
        $this->assertRegExp('#Skipped#', $output);
    }

    public function testSaveContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);

        // Test missing contenttype handled
        $content = new Content($app);
        $this->setExpectedException('Bolt\Exception\StorageException', 'Contenttype is required for saveContent');
        $this->assertFalse($storage->saveContent($content));

        // Test dispatcher is called pre-save and post-save
        $content = $storage->getContent('showcases/1');

        $presave = 0;
        $postsave = 0;
        $listener = function () use (&$presave) {
            $presave++;
        };
        $listener2 = function () use (&$postsave) {
            $postsave++;
        };
        $app['dispatcher']->addListener(StorageEvents::PRE_SAVE, $listener);
        $app['dispatcher']->addListener(StorageEvents::POST_SAVE, $listener2);
        $storage->saveContent($content);
        $this->assertEquals(1, $presave);
        $this->assertEquals(1, $postsave);

    }

    public function testDeleteContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);

        // Test delete fails on missing params
        $this->setExpectedException('Bolt\Exception\StorageException', 'Contenttype is required for deleteContent');
        $this->assertFalse($storage->deleteContent('', 999));

        $content = $storage->getContent('showcases/1');

        // Test the delete events are triggered
        $delete = 0;
        $listener = function () use (&$delete) {
            $delete++;
        };
        $app['dispatcher']->addListener(StorageEvents::PRE_DELETE, $listener);
        $app['dispatcher']->addListener(StorageEvents::POST_DELETE, $listener);

        $storage->deleteContent(array('slug' => 'showcases'), 1);

        $this->assertFalse($storage->getContent('showcases/1'));
        $this->assertEquals(2, $delete);
    }

    public function testUpdateSingleValue()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);
        $fetch1 = $storage->getContent('showcases/2');
        $this->assertEquals(false, $fetch1->get('ownerid'));
        $result = $storage->updateSingleValue('showcases', 2, 'ownerid', '10');
        $this->assertEquals(2, $result);
        $fetch2 = $storage->getContent('showcases/2');
        $this->assertEquals('10', $fetch2->get('ownerid'));

        // Test invalid column fails
        $shouldError = $storage->updateSingleValue('showcases', 2, 'nonexistent', '10');
        $this->assertFalse($shouldError);
    }

    public function testGetEmptyContent()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $showcase = $storage->getEmptyContent('showcase');
        $this->assertInstanceOf('Bolt\Content', $showcase);
        $this->assertEquals('showcases', $showcase->contenttype['slug']);
    }

    public function testSearchContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);
        $result = $storage->searchContent('lorem');
        $this->assertGreaterThan(0, count($result));
        $this->assertTrue($result['query']['valid']);

        // Test invalid query fails
        $result = $storage->searchContent('x');
        $this->assertFalse($result);

        // Test filters
        $result = $storage->searchContent('lorem', array('showcases'), array('showcases' => array('title' => 'nonexistent')));
        $this->assertTrue($result['query']['valid']);
        $this->assertEquals(0, $result['no_of_results']);

    }

    public function testSearchAllContentTypes()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);
        $results = $storage->searchAllContentTypes(array('title' => 'lorem'));
    }

    public function testSearchContentType()
    {

    }

    public function testGetContentByTaxonomy()
    {

    }

    public function testPublishTimedRecords()
    {

    }

    public function testDepublishExpiredRecords()
    {

    }

    public function testGetContent()
    {

    }

    public function testGetSortOrder()
    {

    }

    public function testGetContentType()
    {

    }

    public function testGetTaxonomyType()
    {

    }

    public function testGetContentTypes()
    {

    }

    public function testGetContentTypeFields()
    {

    }

    public function testGetContentTypeFieldType()
    {

    }

    public function testGetContentTypeGrouping()
    {

    }

    public function testGetContentTypeTaxonomy()
    {

    }

    public function testGetLatestId()
    {

    }

    public function testGetUri()
    {

    }

    public function testSetPager()
    {

    }

    public function testGetPager()
    {

    }
}
