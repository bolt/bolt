<?php

namespace Bolt\Tests\Nut\Helper;

use Bolt\Nut\Helper\ContainerHelper;
use PHPUnit\Framework\TestCase;
use Pimple as Container;

/**
 * @covers \Bolt\Nut\Helper\ContainerHelper
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContainerHelperTest extends TestCase
{
    public function testGetContainer()
    {
        $container = new Container([]);
        $container['koala'] = function () {
            return 'Gum leaves';
        };
        $containerHelper = new ContainerHelper($container);

        $this->assertInstanceOf(Container::class, $containerHelper->getContainer());
        $this->assertSame($container, $containerHelper->getContainer());
        $this->assertSame('Gum leaves', $containerHelper->getContainer()->offsetGet('koala'));
    }

    public function testGetName()
    {
        $container = new Container([]);
        $containerHelper = new ContainerHelper($container);
        $this->assertSame('container', $containerHelper->getName());
    }
}
