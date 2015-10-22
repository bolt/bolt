<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\HtmlHandler;
use Bolt\Legacy\Content;

/**
 * Class to test Bolt\Twig\Handler\HtmlHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class HtmlHandlerTest extends BoltUnitTest
{
    public function testCacheHashRelativePath()
    {
        $app = $this->getApp();
        $app['asset.file.hash.factory'] = $app->protect(function ($fileName) {
            return md5($fileName);
        });

        $handler = new HtmlHandler($app);

        $file = str_replace(TEST_ROOT .'/', '', __FILE__);
        $sum = md5(__FILE__);

        $result = $handler->cacheHash($file);
        $this->assertSame($file . '?v=' . $sum, $result);
    }

    public function testCacheHashFullPath()
    {
        $app = $this->getApp();
        $app['asset.file.hash.factory'] = $app->protect(function ($fileName) {
            return md5($fileName);
        });

        $handler = new HtmlHandler($app);

        $sum = md5(__FILE__);
        $result = $handler->cacheHash(__FILE__);
        $this->assertSame(__FILE__ . '?v=' . $sum, $result);
    }

    public function testCacheHashInvalid()
    {
        $app = $this->getApp();

        $handler = new HtmlHandler($app);

        $result = $handler->cacheHash('/where/is/wally/when/you/need/him');
        $this->assertNull($result);
    }

    public function testDecorateTT()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->decorateTT('Lorem `ipsum` dolor.');
        $this->assertSame('Lorem <tt>ipsum</tt> dolor.', $result);
    }

    public function testEditableSafe()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->editable('<blink>Drop Bear Warning!</blink>', new Content($app), 'paddock', true);
        $this->assertNull($result);
    }

    public function testEditable()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);
        $content = new Content($app);
        $content->setValues([
            'id'          => 42,
            'contenttype' => ['slug' => 'snail'],
        ]);

        $result = $handler->editable('<blink>Drop Bear Warning!</blink>', $content, 'paddock', false);
        $this->assertSame('<div class="Bolt-editable" data-id="42" data-contenttype="" data-field="paddock"><blink>Drop Bear Warning!</blink></div>', $result);
    }

    public function testHtmlLang()
    {
        $app = $this->getApp();
        $app['locale'] = 'en_Aussie_Mate';
        $handler = new HtmlHandler($app);

        $result = $handler->htmlLang();
        $this->assertSame('en-Aussie-Mate', $result);
    }
}
