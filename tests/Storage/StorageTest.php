<?php
namespace Bolt\Tests\Storage;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Storage;
use Bolt\Content;

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
        $app = $this->getApp();
        $storage = new Storage($app);
        $output = $storage->prefill(array('showcases'));
        $this->assertRegExp('#Added#', $output);
        $this->assertRegExp('#Done#', $output);
    }
    
    public function testGetChangelog()
    {
        
    }
    
    public function testCountChangelog()
    {
        
    }
    
    public function testGetChangelogByContentType()
    {
        
    }
    
    public function testCountChangelogByContentType()
    {
        
    }
    
    public function testGetChangelogEntry()
    {
        
    }
    
    public function testGetNextChangelogEntry()
    {
        
    }
    
    public function testGetPrevChangelogEntry()
    {
        
    }
    
    public function testSaveContent()
    {
        
    }
    
    public function testDeleteContent()
    {
        
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