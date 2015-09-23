<?php
namespace Bolt\Tests\Profiler;

use Bolt\Profiler\DatabaseDataCollector;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/DataCollector/DatabaseDataCollector.
 *
 * @author Ross Riley <riley.ross@gmail.com>
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
        $request = Request::create('/', 'GET');
        $response = $app->handle($request);

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
        $request = Request::create('/', 'GET');
        $response = $app->handle($request);

        $data->collect($request, $response);
        $this->assertEquals(0, $data->getQueryCount());
    }
}
