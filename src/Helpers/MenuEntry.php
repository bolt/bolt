<?php

namespace Bolt\Helpers;

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
     * @param string    $name
     * @param string    $uri
     * @param MenuEntry $parent
     */
    public function __construct($name, $uri, MenuEntry $parent = null)
    {
        $this->name = $name;
        $this->uri = $uri;
        $this->parent = $parent;
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
     * Set the menu entry's children name.
     *
     * @param MenuEntry $child
     *
     * @return MenuEntry
     */
    public function addChild(MenuEntry $child)
    {
        $this->children[$child->getName()] = $child;

        return $this;
    }

    /**
     * Return a menu entry's named child.
     *
     * @param string $name
     *
     * @return MenuEntry
     */
    public function getChild($name)
    {
        return $this->children[$name];
    }

    /**
     * Return the menu entry's children.
     *
     * @return MenuEntry
     */
    public function getChildren()
    {
        return (array) $this->children;
    }
}
