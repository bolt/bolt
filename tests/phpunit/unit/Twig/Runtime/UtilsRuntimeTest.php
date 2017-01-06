<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\UtilsRuntime;

/**
 * Class to test Bolt\Twig\Runtime\UtilsRuntime
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsRuntimeTest extends BoltUnitTest
{
    /**
     * Override Symfony's default handler to get the output
     *
     * @param Application $app
     */
    protected function stubVarDumper(Application $app)
    {
        $app['dump'] = $app->protect(
            function ($var) {
                return $var;
            }
        );
    }

    public function testFileExists()
    {
        $handler = $this->getHandler();
        $result = $handler->fileExists(__FILE__);
        $this->assertTrue($result);
    }

    public function testPrintBacktraceSafeDebugOn()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $handler = $this->getHandler();

        $result = $handler->printBacktrace(5);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOff()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = false;
        $handler = $this->getHandler();

        $result = $handler->printBacktrace(5);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOffLoggedoff()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', false);
        $handler = $this->getHandler();

        $result = $handler->printBacktrace(5);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOn()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $handler = $this->getHandler();

        $result = $handler->printBacktrace(5);
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('file', $result[0]);
        $this->assertArrayHasKey('line', $result[0]);
        $this->assertArrayHasKey('function', $result[0]);
        $this->assertArrayHasKey('class', $result[0]);
    }

    public function testPrintFirebugSafeDebugOn()
    {
        $app = $this->getApp();
        $app['debug'] = true;
        $handler = $this->getHandler();

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!');
        $this->assertNull($result);
    }

    public function testPrintFirebugNoSafeDebugOff()
    {
        $app = $this->getApp();
        $app['debug'] = false;
        $handler = $this->getHandler();

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!');
        $this->assertNull($result);
    }

    public function testPrintFirebugNoSafeDebugOffLoggedOff()
    {
        $app = $this->getApp();
        $app['debug'] = true;
        $handler = $this->getHandler();

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!');
        $this->assertNull($result);
    }

    public function testPrintFirebugNoSafeDebugOnArrayString()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $logger = $this->getMock('\Monolog\Logger', ['info'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
        ->method('info');
        $app['logger.firebug'] = $logger;

        $handler = $this->getHandler();

        $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!');
    }

    public function testPrintFirebugNoSafeDebugOnStringArray()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $logger = $this->getMock('\Monolog\Logger', ['info'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('info');
        $app['logger.firebug'] = $logger;

        $handler = $this->getHandler();

        $handler->printFirebug('Danger Detected!', ['koala', 'clippy']);
    }

    public function testPrintFirebugNoSafeDebugOnArrayArray()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;

        $logger = $this->getMock('\Monolog\Logger', ['info'], ['testlogger']);
        $logger->expects($this->never())
            ->method('info');
        $app['logger.firebug'] = $logger;

        $handler = $this->getHandler();

        $handler->printFirebug(['koala and clippy'], ['Danger Detected!']);
    }

    /**
     * @return UtilsRuntime
     */
    protected function getHandler()
    {
        $app = $this->getApp();

        return new UtilsRuntime(
            $app['logger.firebug'],
            $app['debug'],
            (bool) $app['users']->getCurrentUser() ?: false,
            $app['config']->get('general/debug_show_loggedoff', false)
        );
    }
}
