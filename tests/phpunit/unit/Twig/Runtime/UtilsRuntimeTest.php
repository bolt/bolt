<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\UtilsRuntime;

/**
 * Class to test Bolt\Twig\Runtime\UtilsRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsRuntimeTest extends BoltUnitTest
{
    public function testFileExists()
    {
        $handler = $this->getHandler();
        $result = $handler->fileExists(__FILE__);
        $this->assertTrue($result);
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
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
        ->method('info');
        $this->setService('logger.firebug', $logger);

        $handler = $this->getHandler();

        $handler->printFirebug(['koala', 'clippy'], 'Danger Detected!');
    }

    public function testPrintFirebugNoSafeDebugOnStringArray()
    {
        $app = $this->getApp();
        $app['debug'] = true;
        $app['config']->set('general/debug_show_loggedoff', true);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('info');
        $this->setService('logger.firebug', $logger);

        $handler = $this->getHandler();

        $handler->printFirebug('Danger Detected!', ['koala', 'clippy']);
    }

    public function testPrintFirebugNoSafeDebugOnArrayArray()
    {
        $app = $this->getApp();
        $app['debug'] = true;

        $logger = $this->getMockMonolog();
        $logger->expects($this->never())
            ->method('info');
        $this->setService('logger.firebug', $logger);

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
