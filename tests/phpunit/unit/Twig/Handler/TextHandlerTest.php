<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\TextHandler;

/**
 * Class to test Bolt\Twig\Handler\TextHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TextHandlerTest extends BoltUnitTest
{
    public function testJsonDecode()
    {
        $app = $this->getApp();
        $handler = new TextHandler($app);

        $array = [
            'koala' => 'gum leaves',
            'extension' => 'Clippy',
        ];

        $result = $handler->jsonDecode(json_encode($array));
        $this->assertSame($array, $result);
    }

    public function testLocaleDateTimeStringNoFormat()
    {
        $app = $this->getApp();
        $handler = new TextHandler($app);

        $result = $handler->localeDateTime('2012-06-14 09:07:55');
        $this->assertSame('June 14, 2012 09:07', $result);
    }

    public function testLocaleDateTimeStringWithFormat()
    {
        $app = $this->getApp();
        $handler = new TextHandler($app);

        $result = $handler->localeDateTime('2012-06-14 09:07:55', '%Y-%m-%d %H:%M');
        $this->assertSame('2012-06-14 09:07', $result);
    }

    public function testLocaleDateTimeObjectNoFormat()
    {
        $app = $this->getApp();
        $handler = new TextHandler($app);

        $dateTime = new \DateTime('2012-06-14 09:07:55');
        $result = $handler->localeDateTime($dateTime);
        $this->assertSame('June 14, 2012 09:07', $result);
    }

    public function testLocaleDateTimeObjectWithFormat()
    {
        $app = $this->getApp();
        $handler = new TextHandler($app);

        $dateTime = new \DateTime('2012-06-14 09:07:55');
        $result = $handler->localeDateTime($dateTime, '%Y-%m-%d %H:%M');
        $this->assertSame('2012-06-14 09:07', $result);
    }
}
