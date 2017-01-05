<?php

namespace Bolt\Tests\Extension;

use Bolt\Tests\Extension\Mock\NormalExtension;
use \Bolt\Tests\Asset\AbstractExtensionsUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test adding of application and local extensions
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionAddTest extends AbstractExtensionsUnitTest
{
    public function testBasicExtensionRegisters()
    {
        $app = $this->makeApp();
        $ext = new NormalExtension();
        $app['extensions']->add($ext);
        $app->initialize();
        $app->handle(Request::createFromGlobals());
        $this->assertArrayHasKey('Bolt/Normal', $app['extensions']->all());
    }

    public function testLocalExtensionRegisters()
    {
        $this->localExtensionInstall();
        $app = $this->makeApp();
        $app['extend.manager.json']->update();
        $app['extend.manager']->dumpAutoload();

        // Now init the local extension
        $app = $this->makeApp();
        $app['extensions']->add(new \TestLocalExtension\Extension());
        $app->initialize();
        $app->handle(Request::createFromGlobals());
        $this->assertArrayHasKey('TestLocalExtension/TestLocalExtension', $app['extensions']->all());
    }
}
