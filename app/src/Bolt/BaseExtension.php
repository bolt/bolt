<?php

namespace Bolt;

abstract class BaseExtension
{
    protected $app;
    protected $basepath;
    protected $namespace;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $baseinfo = array(
            'name' => "-",
            'description' => "-",
            'author' => "-",
            'link' => "-",
            'version' => "0.0",
            'required_bolt_version' => "1.0 RC",
            'highest_bolt_version' => "1.0 RC",
            'type' => "Boilerplate",
            'first_releasedate' => "2013-01-26",
            'latest_releasedate' => "2013-01-26",
            'dependencies' => array(),
            'priority' => 10,
            'tags' => array()
        );

        $this->info = array_merge($baseinfo, $this->info());

        $this->setBasepath();

    }

    /**
     * Set the 'basepath' and the 'namespace' for the extension, since we can't use __DIR__
     *
     * @see http://stackoverflow.com/questions/11117637/getting-current-working-directory-of-an-extended-class-in-php
     *
     */
    public function setBasepath()
    {
        $reflection = new \ReflectionClass($this);
        $this->basepath = dirname($reflection->getFileName());
        $this->namespace = basename(dirname($reflection->getFileName()));
    }

    /**
     * Placeholder for the info function.
     *
     * @return array
     */
    public function info()
    {
        return array();
    }

    /**
     * Get information about the current extension, as an array. Some of these are
     * set by the author of the extension, others are set here.
     *
     * @return array
     */
    public function getInfo()
    {

        if (file_exists($this->basepath . "/readme.md")) {
            $this->info['readme'] = $this->basepath . "/readme.md";
        } else {
            $this->info['readme'] = false;
        }

        if (file_exists($this->basepath . "/config.yml")) {
            $this->info['config'] = $this->namespace . "/config.yml";
            if (is_writable($this->basepath . "/config.yml")) {
                $this->info['config_writable'] = true;
            } else {
                $this->info['config_writable'] = false;
            }
        }

        $this->info['version_ok'] = checkVersion($this->app['bolt_version'], $this->info['required_bolt_version']);
        $this->info['namespace'] = $this->namespace;
        $this->info['basepath'] = $this->basepath;

        return $this->info;
    }

    public function init()
    {

    }


    public function initialize()
    {

    }


    public function addTwigFunction($name, $callback)
    {

        $this->app['twig']->addFunction($name, new \Twig_Function_Function($callback));

    }

    public function addTwigFilter($name, $callback)
    {

        $this->app['twig']->addFunction($name, new \Twig_Function_Function($callback));

    }

    public function insertWidget($type, $location, $callback, $additionalhtml = "", $defer = true, $cacheduration = 180, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->app['extensions']->insertWidget($type, $location, $callback, $this->namespace, $additionalhtml, $defer, $cacheduration, $var1, $var2, $var3);
    }

    public function insertSnippet($name, $callback, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->app['extensions']->insertSnippet($name, $callback, $this->namespace, $var1, $var2, $var3);
    }


    public function parseSnippet($callback, $var1 = "", $var2 = "", $var3 = "")
    {

        if (method_exists($this, $callback)) {
            return call_user_func(array($this, $callback), $var1, $var2, $var3);
        } else {
            return false;
        }

    }


    public function parseWidget($callback, $var1 = "", $var2 = "", $var3 = "")
    {

        if (method_exists($this, $callback)) {
            return call_user_func(array($this, $callback), $var1, $var2, $var3);
        } else {
            return false;
        }

    }

}
