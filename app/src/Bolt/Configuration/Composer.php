<?php

namespace Bolt\Configuration;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;

class Composer extends Standard
{

    /**
     * Constructor initialises on the app root path.
     *
     * @param string $path
     */
    public function __construct($root, Request $request = null)
    {
        parent::__construct($root, $request);
        $this->setPath("composer", "vendor/bolt/bolt");
        $this->setPath("apppath", $this->getPath('composer')."/app");
        $this->setPath("extensionspath", $this->getPath('app')."/extensions");
        $this->setPath("cache", $this->root."/cache");
        $this->setPath("config", $this->root."/config");
        $this->setPath("database", $this->root."/database");
        $this->setPath("themebase", $this->root."/theme");

        $this->setUrl("app", "/bolt-public/");
    }



    public function compat()
    {
        if (!defined("BOLT_COMPOSER_INSTALLED")) {
            define('BOLT_COMPOSER_INSTALLED', true);
        }
        parent::compat();
    }

    /**
     *  This currently gets special treatment because of the processing order.
     *  The theme path is needed before the app has constructed, so this is a shortcut to
     *  allow the Application constructor to pre-provide a theme path.
     *
     * @return void
     **/
    public function setThemePath($generalConfig)
    {
        $theme       = isset($generalConfig['theme']) ? $generalConfig['theme'] : '';
        $theme_path  = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : '/theme';
        $theme_url   = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : $this->getUrl('root').'theme';
        $this->setPath("themepath", sprintf('%s%s/%s', $this->getPath("composer"), $theme_path, $theme));
        $this->setUrl("theme", sprintf('%s/%s/', $theme_url, $theme));
    }
}
