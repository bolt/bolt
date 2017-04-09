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
     */
    final protected function extendMenuService()
    {
        $app = $this->getContainer();

        $app['menu.admin'] = $app->extend(
            'menu.admin',
            function (MenuEntry $menus) {
                /** @var MenuEntry $menus */
                $extendMenu = $menus->get('extensions');

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
        );
    }

    /** @return Container */
    abstract protected function getContainer();
}
