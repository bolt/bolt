<?php

namespace Bolt\Helpers;

use Bolt\Application;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class MenuBuilder
{
    /** @var Application */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function menu($identifier = null, $resolved = true)
    {
        $menus = $this->app['config']->get('menu');

        if (!empty($identifier) && isset($menus[$identifier])) {
            $name = strtolower($identifier);
            $menu = $menus[$identifier];
        } else {
            $name = strtolower(\utilphp\util::array_first_key($menus));
            $menu = \utilphp\util::array_first($menus);
        }

        if (!is_array($menu)) {
            $menu = array();
        }

        if (!$resolved) {
            return new Menu($name, $menu);
        }

        return new Menu($name, $this->resolve($menu), true);
    }

    /**
     * Return a named menu
     *
     * @param array $menu
     *
     * @return array
     */
    public function resolve(array $menu)
    {
        return $this->menuBuilder($menu);
    }

    /**
     * Recursively scans the passed array to ensure everything gets the
     * menuHelper() treatment.
     *
     * @param array $menu
     *
     * @return array
     */
    private function menuBuilder(array $menu)
    {
        foreach ($menu as $key => $item) {
            $menu[$key] = $this->menuHelper($item);
            if (isset($item['submenu'])) {
                $menu[$key]['submenu'] = $this->menuBuilder($item['submenu']);
            }
        }

        return $menu;
    }

    /**
     * Updates a menu item to have at least a 'link' key.
     *
     * @param array $item
     *
     * @return array Keys 'link' and possibly 'label', 'title' and 'path'
     */
    private function menuHelper($item)
    {
        // recurse into submenu's
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            $item['submenu'] = $this->menuHelper($item['submenu']);
        }

        if (isset($item['route'])) {
            $param = !empty($item['param']) ?: array();
            $add = !empty($item['add']) ?: '';

            $item['link'] = Lib::path($item['route'], $param, $add);
        } elseif (isset($item['path'])) {
            $item = $this->resolvePathToContent($item);
        }

        return $item;
    }

    /**
     * Determine the type of path we have.
     *
     * @param array $item
     *
     * @return array
     */
    private function resolvePathToContent(array $item)
    {
        if ($item['path'] === 'homepage') {
            $item['link'] = $this->app['resources']->getUrl('root');

            return $item;
        }

        // We have a mistakenly placed URL, allow it but log it.
        if (preg_match('#^(https?://|//)#i', $item['path'])) {
            $item['link'] = $item['path'];
            $this->app['logger.system']->error(
                Trans::__(
                    'Invalid menu path (%PATH%) set in menu.yml. Probably should be a link: instead!',
                    array('%PATH%' => $item['path'])
                ),
                array('event' => 'config')
            );

            return $item;
        }

        // Get a copy of the path minus trailing/leading slash
        $path = ltrim(rtrim($item['path'], '/'), '/');

        // Pre-set our link in case the match() throws an exception
        $item['link'] = $this->app['resources']->getUrl('root') . $path;

        try {
            // See if we have a 'content/id' or 'content/slug' path
            if (preg_match('#^([a-z0-9_-]+)/([a-z0-9_-]+)$#i', $path)) {

                // Determine if the provided path first matches any routes
                // that we have, this will catch any valid configured
                // contenttype slug and record combination, or throw a
                // ResourceNotFoundException exception otherwise
                $this->app['url_matcher']->match('/' . $path);

                // If we found a valid routing match then we're still here,
                // attempt to retrieve the actual record and use its values.
                $item = $this->populateItemFromRecord($item, $path);
            }
        } catch (ResourceNotFoundException $e) {
            $this->app['logger.system']->error(
                Trans::__(
                    'Invalid menu path (%PATH%) set in menu.yml. Does not match any configured contenttypes or routes.',
                    array('%PATH%' => $item['path'])
                ),
                array('event' => 'config')
            );
        }

        return $item;
    }

    /**
     * Populate a single menu item.
     *
     * @param array  $item
     * @param string $path
     *
     * @return string
     */
    private function populateItemFromRecord(array $item, $path)
    {
        /** @var \Bolt\Content $content */
        $content = $this->app['storage']->getContent($path, array('hydrate' => false));

        if ($content) {
            if (empty($item['label'])) {
                $item['label'] = !empty($content->values['title']) ? $content->values['title'] : '';
            }

            if (empty($item['title'])) {
                $item['title'] = !empty($content->values['subtitle']) ? $content->values['subtitle'] : '';
            }

            $item['link'] = $content->link();
        }

        return $item;
    }
}
