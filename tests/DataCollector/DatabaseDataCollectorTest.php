<?php
namespace Bolt\Tests\DataCollector;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\DataCollector\DatabaseDataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\Logging\DebugStack;

/**
 * Class to test src/DataCollector/DatabaseDataCollector.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class DatabaseDataCollectorTest extends BoltUnitTest
{

    
    public function testBasicData()
    {
        $debug = new DebugStack();
        $data = new DatabaseDataCollector($debug);
        $debug->startQuery("Robert'); DROP TABLE students;");
        $debug->stopQuery();
        
        $app = $this->getApp();
        $request = Request::create('/','GET');
        $app->run($request);
        $response = new Response();
        
        $data->collect($request, $response);
        $this->assertEquals('db', $data->getName());
        $this->assertEquals(1, $data->getQueryCount());
        $this->assertNotEmpty($data->getTime());
        $this->assertNotEmpty($data->getQueries());
    }
    
    public function testPragmaIgnored()
    {
        $debug = new DebugStack();
        $data = new DatabaseDataCollector($debug);
        $debug->startQuery("PRAGMA test");
        $debug->stopQuery();
        
        $app = $this->getApp();
        $request = Request::create('/','GET');
        $app->run($request);
        $response = new Response();
        
        $data->collect($request, $response);
        $this->assertEquals(0, $data->getQueryCount());

    }
    

   
}
