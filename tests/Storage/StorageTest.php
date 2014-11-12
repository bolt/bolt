<?php
namespace Bolt\Tests\Storage;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Storage;
use Bolt\Content;
use Bolt\Events\StorageEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class to test src/Storage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StorageTest extends BoltUnitTest
{


    public function testSetup()
    {
        $app = $this->getApp();
        $app['config']->set('general/database/prefix', "bolt");
        $storage = new Storage($app);
        $this->assertEquals('bolt_', \PHPUnit_Framework_Assert::readAttribute($storage, 'prefix'));
    }
    
    public function testGetContentObject()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $content = $storage->getContentObject('pages');
        $this->assertInstanceOf('Bolt\Content', $content);
        
        
        $fields = $app['config']->get('contenttypes/pages/fields');
        
        $mock = $this->getMock('Bolt\Content',null,array($app), 'Pages');
        $content = $storage->getContentObject(array('class'=>'Pages','fields'=>$fields));
        $this->assertInstanceOf('Pages', $content);
        $this->assertInstanceOf('Bolt\Content', $content);
        
        // Test that a class not instanceof Bolt\Content fails
        $mock = $this->getMock('stdClass',null,array(), 'Failing');
        $this->setExpectedException('Exception', 'Failing does not extend \Bolt\Content.');
        $content = $storage->getContentObject(array('class'=>'Failing','fields'=>$fields));
    }
    
    public function testPreFill()
    {
        $app = $this->makeApp();
        $app['resources']->setPath('files', TEST_ROOT . '/tests/resources');
        $app['config']->set('general/changelog/enabled', true);
        $app->initialize();
        $storage = new Storage($app);
        $output = $storage->prefill(array('showcases'));
        $this->assertRegExp('#Added#', $output);
        $this->assertRegExp('#Done#', $output);
        
        $output = $storage->prefill();
        $this->assertRegExp('#Skipped#', $output);
    }
    
    public function testGetChangelog()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);

        $content = $storage->getContentObject('pages');
        $storage->saveContent($content, 'pages');
        $logs = $storage->getChangeLog(array('limit'=>1,'offset'=>0,'order'=>'id'));
        $logs2 = $storage->getChangeLog(array('limit'=>1));
        $this->assertEquals(1, count($logs));
        $this->assertEquals(1, count($logs2));
    }
    
    public function testCountChangelog()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $count = $storage->countChangelog();
        $this->assertNotEmpty($count);
    }
    
    public function testGetChangelogByContentType()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $log = $storage->getChangelogByContentType('pages', array('limit'=>1,'offset'=>0,'order'=>'id'));
        $this->assertEquals(1, count($log));
    }
    
    public function testGetChangelogByContentTypeArray()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $log = $storage->getChangelogByContentType(array('slug'=>'pages'), array('limit'=>1,'contentid'=>6));
        $this->assertEquals(1, count($log));
    }
    
    public function testCountChangelogByContentType()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $count = $storage->countChangelogByContentType('pages', array());
        $this->assertGreaterThan(0, $count);
        
        $count = $storage->countChangelogByContentType('showcases', array('contentid'=>1));
        $this->assertGreaterThan(0, $count);
        
        $count = $storage->countChangelogByContentType(array('slug'=>'showcases'), array('id'=>1));
        $this->assertGreaterThan(0, $count);
    }
    
    public function testGetChangelogEntry()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $storage = new Storage($app);
        //$all = $storage->getChangelogByContentType('pages', array());


        
        $log = $storage->getChangelogEntry('showcases',1,1);
        $this->assertInstanceOf('Bolt\ChangelogItem', $log);
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
        $content->setValues(array('status'=>'draft','ownerid'=>99));
        $storage->saveContent($content, 'Test Suite Update');
        $content->setValues(array('status'=>'published','ownerid'=>1));
        $storage->saveContent($content, 'Test Suite Update');
        
                
        $log = $storage->getNextChangelogEntry('pages', 1, 1);
        $this->assertInstanceOf('Bolt\ChangelogItem', $log);
        $this->assertAttributeEquals(1, 'contentid', $log);
    }
    
    public function testGetPrevChangelogEntry()
    {
        $app = $this->getApp();
        $storage = new Storage($app);
        $log = $storage->getPrevChangelogEntry('pages', 1, 10);
        $this->assertInstanceOf('Bolt\ChangelogItem', $log);
        $this->assertAttributeEquals(1, 'contentid', $log);
    }
    
    public function testSaveContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = new Storage($app);
        
        // Test missing contenttype handled
        $content = new Content($app);
        $this->expectOutputString('Contenttype is required.');
        $this->assertFalse($storage->saveContent($content));
        
        // Test dispatcher is called pre-save and post-save
        $content = $storage->getContent('showcases/1');

        $presave = 0;
        $postsave = 0;
        $listener = function() use(&$presave) {
            $presave++;
        };
        $listener2 = function() use(&$postsave) {
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
        $this->expectOutputString('Contenttype is required.');
        $this->assertFalse($storage->deleteContent('', 999));
        
        $content = $storage->getContent('showcases/1');
        
        // Test the delete events are triggered
        $delete = 0;
        $listener = function() use(&$delete) {
            $delete++;
        };
        $app['dispatcher']->addListener(StorageEvents::PRE_DELETE, $listener);
        $app['dispatcher']->addListener(StorageEvents::POST_DELETE, $listener);
        
        $storage->deleteContent('showcases',1);
        
        $this->assertFalse($storage->getContent('showcases/1'));
        $this->assertEquals(2, $delete);
    }
    
    public function testUpdateSingleValue()
    {
           
    }
    
    public function testGetEmptyContent()
    {
        
    }
    
    public function testSearchContent()
    {
        
    }
    
    public function testSearchAllContentTypes()
    {
        
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
    
    public function testGetContentTypeAssert()
    {
        
    }
    
    public function testGetTaxonomyTypeAssert()
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