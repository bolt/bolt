<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\UtilsHandler;

/**
 * Class to test Bolt\Twig\Handler\UtilsHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsHandlerTest extends BoltUnitTest
{
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
}
