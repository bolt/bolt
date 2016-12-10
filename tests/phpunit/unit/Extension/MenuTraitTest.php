<?php

namespace Bolt\Tests\Extension;

use Bolt\Menu\MenuEntry;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\MenuExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\MenuTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuTraitTest extends BoltUnitTest
{
    public function testEmptyMenus()
    {
        $app = $this->getApp();
        $ext = new NormalExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/menu');
        $ext->setBaseDirectory($baseDir);
        $ext->setContainer($app);
        $ext->register($app);

        /** @var MenuEntry $extendMenu */
        $extendMenu = $app['menu.admin']->get('extensions');
        $this->assertSame('extensions', $extendMenu->getName());
        $this->assertSame('Extensions Overview', $extendMenu->getLabel());
        $this->assertSame([], $extendMenu->children());
    }

    public function testMenuAdds()
    {
        $app = $this->getApp();

        $ext = new MenuExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/menu');
        $ext->setBaseDirectory($baseDir);
        $ext->setContainer($app);
        $ext->register($app);
        /** @var MenuEntry $extendMenu */
        $extendMenu = $app['menu.admin']->get('extensions');
        $children = $extendMenu->children();

        $this->assertSame('koala', $children['koala']->getName());
        $this->assertSame('Koalas', $children['koala']->getLabel());
        $this->assertSame('/bolt/extensions/koalas-are-us', $children['koala']->getUri());
        $this->assertSame('fa-thumbs-o-up', $children['koala']->getIcon());
        $this->assertSame('config', $children['koala']->getPermission());

        $this->assertSame('Drop Bear', $children['Drop Bear']->getName());
        $this->assertSame('Drop Bear', $children['Drop Bear']->getLabel());
        $this->assertSame('/bolt/extensions/look-up-live', $children['Drop Bear']->getUri());
        $this->assertSame('fa-thumbs-o-down', $children['Drop Bear']->getIcon());
        $this->assertSame('dangerous', $children['Drop Bear']->getPermission());
    }
}
