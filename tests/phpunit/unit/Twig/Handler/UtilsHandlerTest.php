<?php

namespace Bolt\Tests\Twig;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\UtilsHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test Bolt\Twig\Handler\UtilsHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsHandlerTest extends BoltUnitTest
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
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, true);
        $this->assertNull($result);
    }

    public function testPrintBacktraceNoSafeDebugOff()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = false;
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, false);
        $this->assertNull($result);
    }


    public function testPrintBacktraceNoSafeDebugOffLoggedoff()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', false);
        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, false);
        $this->assertNull($result);
    }


    public function testPrintBacktraceNoSafeDebugOn()
    {
        $app = $this->getApp();
        $this->stubVarDumper($app);
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $handler = new UtilsHandler($app);

        $result = $handler->printBacktrace(5, false);
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
        $handler = new UtilsHandler($app);

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!', true);
        $this->assertNull($result);
    }

    public function testPrintFirebugNoSafeDebugOff()
    {
        $app = $this->getApp();
        $app['debug'] = false;
        $handler = new UtilsHandler($app);

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!', true);
        $this->assertNull($result);
    }

    public function testPrintFirebugNoSafeDebugOffLoggedOff()
    {
        $app = $this->getApp();
        $app['debug'] = true;
        $handler = new UtilsHandler($app);

        $result = $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!', true);
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

        $handler = new UtilsHandler($app);

        $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!', false);
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

        $handler = new UtilsHandler($app);

        $handler->printFirebug('Danger Detected!', ['koala', 'clippy'], false);
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

        $handler = new UtilsHandler($app);

        $handler->printFirebug(['koala and clippy'], ['Danger Detected!'], false);
    }

    public function testRedirectSafe()
    {
        $app = $this->getApp();
        $handler = new UtilsHandler($app);

        $result = $handler->redirect('/clippy/koala', true);
        $this->assertNull($result);
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testRedirectNoSafe()
    {
        if (phpversion('xdebug') === false) {
            $this->markTestSkipped('No xdebug support enabled.');
        }

        $app = $this->getApp();
        $handler = new UtilsHandler($app);

        $this->expectOutputRegex('/Redirecting to/i');
        $handler->redirect('/clippy/koala', false);
        $this->assertContains('location: /clippy/koala', xdebug_get_headers());
    }

    public function testRequestSafe()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $app['request'] = $request;
        $handler = new UtilsHandler($app);

        $result = $handler->request('route', 'GET', true, true);
        $this->assertNull($result);
    }

    public function testRequestGet()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->query->set('koala', 'gum leaves');
        $app['request'] = $request;
        $handler = new UtilsHandler($app);

        $result = $handler->request('koala', 'GET', true, false);
        $this->assertSame('gum leaves', $result);
    }

    public function testRequestPost()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->request->set('koala', 'gum leaves');
        $app['request'] = $request;
        $handler = new UtilsHandler($app);

        $result = $handler->request('koala', 'POST', true, false);
        $this->assertSame('gum leaves', $result);
    }

    public function testRequestPatch()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->attributes->set('koala', 'gum leaves');
        $app['request'] = $request;
        $handler = new UtilsHandler($app);

        $result = $handler->request('koala', 'PATCH', true, false);
        $this->assertSame('gum leaves', $result);
    }
}
