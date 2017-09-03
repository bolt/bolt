<?php

namespace Bolt\Extension;

use Bolt\Menu\MenuEntry;
use Pimple\Container;

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
     *
     * @throws \InvalidArgumentException
     */
    final protected function extendMenuService()
    {
        $app = $this->getContainer();

        $app['menu.admin_builder'] = $app->extend(
            'menu.admin_builder',
            function (MenuEntry $menus) {
                if (!$menus->has('custom')) {
                    return $menus;
                }

                /** @var MenuEntry $menus */
                $extendMenu = $menus->get('custom');

                foreach ($this->registerMenuEntries() as $menuEntry) {
                    if (!$menuEntry instanceof MenuEntry) {
                        throw new \InvalidArgumentException(sprintf(
                            '%s::registerMenuEntries() should return a list of Bolt\Menu\MenuEntry objects. Got: %s',
                            static::class,
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
        );
    }

    /** @return Container */
    abstract protected function getContainer();
}
