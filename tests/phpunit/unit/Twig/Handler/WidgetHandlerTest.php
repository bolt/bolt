<?php

namespace Bolt\Tests\Twig;

use Bolt\Asset\Widget\Widget;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\WidgetHandler;

/**
 * Class to test Bolt\Twig\Handler\WidgetHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetHandlerTest extends BoltUnitTest
{
    public function testWidget()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setType('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $result = $handler->widgets('frontend', 'gum-tree');
        $this->assertRegExp('#<div class="widgetholder widgetholder-gum-tree">#', $result);
        $this->assertRegExp('#<blink>Drop Bear Warning!</blink>#', $result);
    }
}
