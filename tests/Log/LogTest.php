<?php
namespace Bolt\Tests\Log;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Log;
use Bolt\Storage;

/**
 * Class to test src/Log.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class LogTest extends BoltUnitTest
{


    public function testSetup()
    {
        $app = $this->getApp();
        $log = new Log($app);
        $this->assertEquals('bolt_', \PHPUnit_Framework_Assert::readAttribute($log, 'prefix'));
        $this->assertEquals('bolt_log', \PHPUnit_Framework_Assert::readAttribute($log, 'tablename'));

    }
    
    public function testSetRoute()
    {
        $app = $this->getApp();
        $log = new Log($app);
        $log->setRoute('test');
        $this->assertEquals('test', \PHPUnit_Framework_Assert::readAttribute($log, 'route'));
    }
    
    
    public function testErrorHandler()
    {
        $app = $this->getApp();
        $log = new Log($app);
        $log->errorhandler('fail','fail.php',10);
        $logs = $log->getMemoryLog();
        $this->assertEquals(1, count($logs));
    }
    
    public function testAdd()
    {
        $app = $this->getApp();
        $log = new Log($app);
        $log->add('Important',1);
        $logs = $log->getMemoryLog();
        $this->assertEquals('Important',$logs[0]['message']);
        $this->assertEquals(1,$logs[0]['level']);
        
        // Test debug off mode
        $app = $this->getApp();
        $app['debug'] = false;
        $log = new Log($app);
        $log->add('Test',1);
        $this->assertEquals(0, count($log->getMemoryLog()));
        
        // Test that content objects get handled
        $app = $this->getApp();
        $log = new Log($app);
        $storage = new Storage($app);
        $content = $storage->getEmptyContent('showcases');
        $log->add('Content',1,$content);
        $logs = $log->getMemoryLog();
        $this->assertEquals('Content',$logs[0]['message']);
        $this->assertEquals('showcases',$logs[0]['contenttype']);

    }
    
    public function testGetActivity()
    {
        
    }
    

    
    
 
   
}