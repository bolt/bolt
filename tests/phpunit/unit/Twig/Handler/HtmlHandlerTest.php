<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\HtmlHandler;

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
}
