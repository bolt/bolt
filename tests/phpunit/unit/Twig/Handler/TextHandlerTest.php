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
}
