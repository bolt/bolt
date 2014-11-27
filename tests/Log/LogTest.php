<?php
namespace Bolt\Tests\Log;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Log;
use Bolt\Storage;
use Bolt\Tests\Mocks\DoctrineMockBuilder;
use Symfony\Component\HttpFoundation\Request;


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
        $app = $this->getApp();
        $phpunit = $this;
        $mocker = new DoctrineMockBuilder;
        $db = $mocker->getConnectionMock();
        $queries = array();
        $db->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnCallback(function($query, $params) use(&$queries, $mocker) {
                $queries[] = array($query, $params);
                return $mocker->getStatementMock();
            }));
        
            
        $app['db'] = $db;       
        // Create a routed request which is needed to test logging
        $log = new Log($app);
        $request = Request::create('/');     
        $app->before(function($request, $app) use($phpunit, $log, &$queries){

            $log->getActivity();
            
            // We should have 3 queries stored, ignore the first as this is a generic user fetch
            array_shift($queries);
            $phpunit->assertEquals(
                "SELECT * FROM bolt_log WHERE code IN (?) OR (level >= ?) ORDER BY date DESC LIMIT 10 OFFSET 0",
                $queries[0][0]
            );
            $phpunit->assertEquals(
                "SELECT count(*) as count FROM bolt_log WHERE code IN (?) OR (level >= ?)", 
                $queries[1][0]
            );
        });
        $app->handle($request);            
    
        
        
    }
    
    public function testValues()
    {
        $app = $this->getApp();
        $log = new Log($app);
        $log->setValue('test','testing');
        $this->assertEquals('testing', $log->getValue('test'));
        $this->assertFalse($log->getValue('notset'));
        $this->assertEquals(1, count($log->getValues()) );
    }
    
    
    public function testTrim()
    {
        $app = $this->getApp();
        $phpunit = $this;
        $mocker = new DoctrineMockBuilder;
        $db = $mocker->getConnectionMock();
        $queries = array();
        $db->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnCallback(function($query, $params) use(&$queries, $mocker) {
                $queries[] = array($query, $params);
                return $mocker->getStatementMock();
            }));
        
            
        $app['db'] = $db; 
        $log = new Log($app);
        $log->trim();
        $this->assertEquals("DELETE FROM bolt_log WHERE level='1';", $queries[0][0]);
        $this->assertEquals("DELETE FROM bolt_log WHERE level='2' AND date < ?;", $queries[1][0]);
        $this->assertEquals("DELETE FROM bolt_log WHERE date < ?;", $queries[2][0]);
        
        $log->clear();
        $this->assertEquals(
            "DELETE FROM bolt_log; UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = 'bolt_log'", 
            $queries[3][0]
        );
        
        // Simulate non sqlite query too
        $app['config']->set('general/database/driver', 'pdo_mysql');
        $log->clear();
        $this->assertEquals(
            "TRUNCATE bolt_log;", 
            $queries[4][0]
        );
    }
    

    
    
 
   
}