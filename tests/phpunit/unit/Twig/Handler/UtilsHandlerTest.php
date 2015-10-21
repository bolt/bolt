<?php

namespace Bolt\Tests\Twig;

use Bolt\Twig\Handler\UtilsHandler;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class to test Bolt\Twig\Handler\UtilsHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsHandlerTest extends BoltUnitTest
{
    protected function tearDown()
    {
        parent::tearDown();
        VarDumper::setHandler(null);
    }

    /**
     * Override Symfony's default handler to get the output
     */
    protected function stubVarDumper()
    {
        VarDumper::setHandler(
            function ($var) {
                return $var;
            }
        );
    }

    public function testFileExists()
    {
        $app = $this->getApp();
        $handler = new UtilsHandler($app);

        $result = $handler->fileExists(__FILE__, false);
        $this->assertTrue($result);
    }

    public function testFileExistsSafe()
    {
        $app = $this->getApp();
        $handler = new UtilsHandler($app);

        $result = $handler->fileExists(__FILE__, true);
        $this->assertFalse($result);
    }

    public function testPrintBacktraceSafeDebugOn()
    {
        $app = $this->getApp();
        $app['debug'] = true;
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, true);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOff()
    {
        $app = $this->getApp();
        $app['debug'] = false;
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, false);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOn()
    {
        $this->stubVarDumper();

        $app = $this->getApp();
        $app['debug'] = true;
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, false);
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('file', $result[0]);
        $this->assertArrayHasKey('line', $result[0]);
        $this->assertArrayHasKey('function', $result[0]);
        $this->assertArrayHasKey('class', $result[0]);
    }
}
