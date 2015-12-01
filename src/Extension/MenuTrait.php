<?php

namespace Bolt\Extension;

use Bolt\Menu\MenuEntry;

/**
 * Admin menu handling trait for an extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait MenuTrait
{
    /** @return \Silex\Application */
    abstract protected function getApp();

    /**
     * Add a menu option to backend menu.
     *
     * @param string $label
     * @param string $path
     * @param string $icon
     * @param string $permission
     */
    protected function addMenuEntry($label, $path, $icon = null, $permission = null)
    {
        /** @var MenuEntry $menus */
        $menus = $this->getApp()['menu.admin'];
        $child = (new MenuEntry($label, $path))
            ->setLabel($label)
            ->setIcon($icon)
            ->setPermission($permission)
        ;

        $menus->getChild('extend')->addChild($child);
    }
}
