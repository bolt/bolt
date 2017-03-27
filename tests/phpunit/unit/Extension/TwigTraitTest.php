<?php

namespace Bolt\Tests\Extension;

use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\TwigExtension;
use Twig_Loader_Array as ArrayLoader;

/**
 * Class to test Bolt\Extension\TwigTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TwigTraitTest extends BoltUnitTest
{
    public function testTwigExtension()
    {
        $app = $this->getApp(false);
        $ext = new TwigExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/twig');
        $ext->setBaseDirectory($baseDir);
        $ext->setContainer($app);
        $ext->register($app);
        $app->boot();

        $this->addToAssertionCount(1);
    }

    public function testRenderTemplate()
    {
        $app = $this->getApp();
        $template = <<< TWIG
Function koala {{ koala(name) }}
Function dropbear {{ dropbear(name) }}
Filter koala {{ name|koala }}
Filter dropbear {{ name|dropbear }}
TWIG;
        $loader = new ArrayLoader(['marsupial.twig' => $template]);
        $app['twig']->setLoader($loader);

        $ext = new TwigExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/twig');
        $ext->setBaseDirectory($baseDir);
        $ext->setContainer($app);
        $ext->register($app);

        $context = ['name' => 'Kenny Koala'];
        $html = $ext->getTestTemplateOutput('marsupial.twig', $context);

        $this->assertRegExp('/Function koala kenny koala/', $html);
        $this->assertRegExp('/Function dropbear kenny koala/', $html);
        $this->assertRegExp('/Filter koala KENNY KOALA/', $html);
        $this->assertRegExp('/Filter dropbear KENNY KOALA/', $html);
    }
}
