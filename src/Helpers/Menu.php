<?php

namespace Bolt\Helpers;

/**
 * Class Menu
 */
class Menu
{
    /** @var string */
    private $name;
    /** @var array */
    private $menu;
    /** @var bool */
    private $resolved;

    /**
     * @param string $name     The name of the menu
     * @param array  $menu     The items that the menu contains
     * @param bool   $resolved
     */
    public function __construct($name, array $menu, $resolved = false)
    {
        $this->name     = $name;
        $this->menu     = $menu;
        $this->resolved = $resolved;
    }

    /**
     * The name of the menu.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The item that the menu contains.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->menu;
    }

    /**
     * Has this menu had it's paths resolved to links.
     *
     * @return bool
     */
    public function isResolved()
    {
        return $this->resolved;
    }
}
