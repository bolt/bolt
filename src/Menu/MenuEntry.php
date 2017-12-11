<?php

namespace Bolt\Menu;

use Bolt\Common\Serialization;
use GuzzleHttp\Psr7\Uri;
use Serializable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A menu entry item.
 *
 * @internal Do not extend. Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class MenuEntry implements Serializable
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
    /** @var bool */
    protected $group = false;

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
    public function __construct($name, $uri = null)
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    /**
     * @param string $name
     * @param string $uri
     *
     * @return MenuEntry
     */
    public static function create($name, $uri = null)
    {
        return new static($name, $uri);
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
        if ($this->routeGenerated) {
            return $this->routeGenerated;
        }

        if ($this->routeName !== null) {
            return $this->routeGenerated = $this->urlGenerator->generate($this->routeName, $this->routeParams);
        }

        if (strpos($this->uri, '/') === 0 || !$this->parent) {
            return $this->routeGenerated = $this->uri;
        }

        $parentUri = $this->parent ? $this->parent->getUri() : null;
        if ($parentUri === null) {
            return $this->routeGenerated = $this->uri;
        }

        $routeGenerated = new Uri($parentUri);
        $routeGenerated = $routeGenerated->withPath($routeGenerated->getPath() . '/' . $this->uri);

        return $this->routeGenerated = (string) $routeGenerated;
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
     * Check if menu entry is a group.
     *
     * @return bool
     */
    public function isGroup()
    {
        return $this->group;
    }

    /**
     * Set if the menu entry is a group.
     *
     * @param bool $group
     *
     * @return MenuEntry
     */
    public function setGroup($group)
    {
        $this->group = $group;

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
        if (isset($this->children[$name])) {
            return $this->children[$name];
        }

        throw new \InvalidArgumentException(sprintf('Menu entry %s does not have a child named "%s"', $this->name, $name));
    }

    /**
     * Returns true if the child is defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->children[$name]);
    }

    /**
     * Remove a menu entry's named child.
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->children[$name]);
    }

    /**
     * Return the menu entry's parent.
     *
     * @return MenuEntry
     */
    public function parent()
    {
        return $this->parent;
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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return Serialization::dump([
            'parent'     => $this->parent,
            'children'   => $this->children,
            'name'       => $this->name,
            'label'      => $this->label,
            'icon'       => $this->icon,
            'permission' => $this->getPermission(),
            'uri'        => $this->getUri(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = Serialization::parse($serialized);

        $this->parent = $data['parent'];
        $this->children = $data['children'];
        $this->name = $data['name'];
        $this->label = $data['label'];
        $this->icon = $data['icon'];
        $this->permission = $data['permission'];
        $this->uri = $data['uri'];
    }
}
