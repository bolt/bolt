<?php

namespace Bolt\Extension;

use Bolt\Menu\MenuEntry;
use Pimple as Container;

/**
 * Admin menu handling trait for an extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
trait MenuTrait
{
    /** @var MenuEntry[] */
    private $menuEntries;

    /**
     * Returns a list of menu entries to register.
     *
     * @return MenuEntry[]
     */
    protected function registerMenuEntries()
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendMenuService()
    {
        $app = $this->getContainer();

        $app['menu.admin'] = $app->share(
            $app->extend(
                'menu.admin',
                function (MenuEntry $menus) {
                    /** @var MenuEntry $menus */
                    $extendMenu = $menus->get('extend');

                    foreach ($this->registerMenuEntries() as $menuEntry) {
                        if (!$menuEntry instanceof MenuEntry) {
                            throw new \InvalidArgumentException(sprintf(
                                '%s::registerMenuEntries() should return a list of Bolt\Menu\MenuEntry objects. Got: %s',
                                get_called_class(),
                                get_class($menuEntry)
                            ));
                        }

                        $extendMenu->add($menuEntry);
                    }
                    foreach ((array) $this->menuEntries as $menuEntry) {
                        $extendMenu->add($menuEntry);
                    }

                    return $menus;
                }
            )
        );
    }

    /**
     * Add a menu option to backend menu.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use registerMenuEntries() instead.
     *
     * @param string $label
     * @param string $path
     * @param string $icon
     * @param string $permission
     */
    final protected function addMenuEntry($label, $path, $icon = null, $permission = null)
    {
        $this->menuEntries[] = (new MenuEntry($label, $path))
            ->setLabel($label)
            ->setIcon($icon)
            ->setPermission($permission)
        ;
    }

    /** @return Container */
    abstract protected function getContainer();
}
