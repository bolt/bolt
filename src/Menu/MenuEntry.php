<?php

namespace Bolt\Menu;

use LogicException;

/**
 * A menu entry item.
 *
 * @internal Do not extend. Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuEntry
{
    /** @var MenuEntry */
    protected $parent;
    /** @var MenuEntry[] */
    protected $children;
    /** @var string */
    protected $name;
    /** @var string */
    protected $label;
    /** @var string */
    protected $uri;
    /** @var string */
    protected $icon;
    /** @var string */
    protected $permission;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $uri
     */
    public function __construct($name, $uri)
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    /**
     * Return the menu entry's internal name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the menu entry's URI relative to Bolt admin's.
     *
     * @return string
     */
    public function getUri()
    {
        if (strpos($this->uri, '/') === 0) {
            return $this->uri;
        }

        return $this->parent ? $this->parent->getUri() . '/' . $this->uri : $this->uri;
    }

    /**
     * Return the menu entry's label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the menu entry's label.
     *
     * @param string $label
     *
     * @return MenuEntry
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Return the menu entry's icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set the menu entry's icon.
     *
     * @param string $icon
     *
     * @return MenuEntry
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Return the menu entry's required view permission.
     *
     * @return string
     */
    public function getPermission()
    {
        return $this->permission ?: 'everyone';
    }

    /**
     * Set the menu entry's required view permission.
     *
     * @param string $permission
     *
     * @return MenuEntry
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Add child menu entry.
     *
     * @param MenuEntry $menu
     *
     * @return MenuEntry
     */
    public function add(MenuEntry $menu)
    {
        $name = $menu->getName();
        $menu->setParent($this);
        $this->children[$name] = $menu;

        return $this->children[$name];
    }

    /**
     * Return a menu entry's named child.
     *
     * @param string $name
     *
     * @return MenuEntry
     */
    public function get($name)
    {
        return $this->children[$name];
    }

    /**
     * Return the menu entry's children.
     *
     * @return MenuEntry[]
     */
    public function children()
    {
        return (array) $this->children;
    }

    /**
     * Set the menu entry's parent object.
     *
     * @param MenuEntry $parent
     *
     * @return MenuEntry
     */
    public function setParent(MenuEntry $parent)
    {
        if ($this->parent !== null) {
            throw new LogicException('Parent menu association can not be changed after being set.');
        }

        $this->parent = $parent;

        return $this;
    }
}
