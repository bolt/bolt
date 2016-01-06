<?php

namespace Bolt\Tests\Menu;

use Bolt\Menu\MenuEntry;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Menu/MenuEntry.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuEntryTest extends BoltUnitTest
{
    public function testCreateRoot()
    {
        $app = $this->getApp();
        $rootEntry = new MenuEntry('root', $app['config']->get('general/branding/path'));
        $this->assertInstanceOf('Bolt\Menu\MenuEntry', $rootEntry);
        $this->assertSame('/bolt', $rootEntry->getUri());
        $this->assertSame('root', $rootEntry->getName());
        $this->assertNull($rootEntry->getIcon());
        $this->assertSame('everyone', $rootEntry->getPermission());

        $app['config']->set('general/branding/path', '/koala/drop-bear');
        $rootEntry = new MenuEntry('root', $app['config']->get('general/branding/path'));
        $this->assertSame('/koala/drop-bear', $rootEntry->getUri());
    }

    public function testCreateChild()
    {
        $app = $this->getApp();
        $rootEntry = new MenuEntry('root', $app['config']->get('general/branding/path'));
        $extendEntry = $rootEntry->add(
            (new MenuEntry('dropbear', 'drop-bears'))
            ->setLabel('Furry Animals')
            ->setIcon('fa:koala')
            ->setPermission('strict')
        );

        $this->assertSame('/bolt/drop-bears', $extendEntry->getUri());
        $this->assertSame('dropbear', $extendEntry->getName());
        $this->assertSame('Furry Animals', $extendEntry->getLabel());
        $this->assertSame('fa:koala', $extendEntry->getIcon());
        $this->assertSame('strict', $extendEntry->getPermission());
        $this->assertSame('/bolt/drop-bears', $extendEntry->getUri());

        $this->assertSame('/bolt/drop-bears', $rootEntry->get('dropbear')->getUri());
        $this->assertSame('dropbear', $rootEntry->get('dropbear')->getName());
        $this->assertSame('Furry Animals', $rootEntry->get('dropbear')->getLabel());
        $this->assertSame('fa:koala', $rootEntry->get('dropbear')->getIcon());
        $this->assertSame('strict', $rootEntry->get('dropbear')->getPermission());
        $this->assertSame('/bolt/drop-bears', $rootEntry->get('dropbear')->getUri());

        $firstBorn = $rootEntry->children();
        $this->assertInstanceOf('Bolt\Menu\MenuEntry', $firstBorn['dropbear']);
        $this->assertSame('/bolt/drop-bears', $firstBorn['dropbear']->getUri());
        $this->assertSame('dropbear', $firstBorn['dropbear']->getName());
        $this->assertSame('Furry Animals', $firstBorn['dropbear']->getLabel());
        $this->assertSame('fa:koala', $firstBorn['dropbear']->getIcon());
        $this->assertSame('strict', $firstBorn['dropbear']->getPermission());
        $this->assertSame('/bolt/drop-bears', $firstBorn['dropbear']->getUri());
    }
}
