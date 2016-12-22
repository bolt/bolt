<?php

namespace Bolt\Menu;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A menu entry item.
 *
 * @internal Do not extend. Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class MenuEntry
{
    /** @var MenuEntry|null */
    protected $parent;
    /** @var MenuEntry[] */
    protected $children = [];

    /** @var string */
    protected $name;
    /** @var string */
    protected $label;
    /** @var string */
    protected $icon;
    /** @var string */
    protected $permission;

    /** @var string */
    protected $uri;
    /** @var string */
    protected $routeName;
    /** @var array */
    protected $routeParams;
    /** @var string */
    protected $routeGenerated;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /**
     * Create the root menu entry.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param string                $basePath
     *
     * @return MenuEntry
     */
    public static function createRoot(UrlGeneratorInterface $urlGenerator, $basePath)
    {
        $root = new static('root', $basePath);
        $root->urlGenerator = $urlGenerator;

        return $root;
    }

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $uri
     */
    public function __construct($name, $uri = '')
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    /**
     * Set the uri to be generated with given route name and params.
     *
     * @param string $routeName
     * @param array  $routeParams
     *
     * @return MenuEntry
     */
    public function setRoute($routeName, $routeParams = [])
    {
        $this->routeName = $routeName;
        $this->routeParams = $routeParams;
        $this->routeGenerated = null;

        return $this;
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
        if ($this->routeName !== null) {
            if ($this->routeGenerated === null) {
                $this->routeGenerated = $this->urlGenerator->generate($this->routeName, $this->routeParams);
            }

            return $this->routeGenerated;
        }

        if (strpos($this->uri, '/') === 0 || !$this->parent) {
            return $this->uri;
        }

        return $this->parent->getUri() . '/' . $this->uri;
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
        $menu->parent = $this;
        $menu->urlGenerator = $this->urlGenerator;
        $this->children[$menu->getName()] = $menu;

        return $menu;
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
        return $this->children;
    }
}
