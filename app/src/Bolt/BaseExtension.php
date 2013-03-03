<?php

namespace Bolt;

interface BaseExtensionInterface
{
    public function __construct(Application $app);
    public function initialize();
    public function getInfo();
}


abstract class BaseExtension extends \Twig_Extension implements BaseExtensionInterface
{
    protected $app;
    protected $basepath;
    protected $namespace;
    protected $functionlist;
    protected $filterlist;
    protected $snippetlist;

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

        // Don't get config just yet. Let 'Extensions' handle this when activating.
        // $this->getConfig();

        $this->functionlist = array();
        $this->filterlist = array();
        $this->snippetlist = array();

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
     * Get the config file. If it doesn't exist, attempt to fall back to config.yml.dist,
     * and rename it to config.yml.
     *
     * @return array
     */
    public function getConfig()
    {
        $configfile = $this->basepath . '/config.yml';
        $configdistfile = $this->basepath . '/config.yml.dist';

        // If it's readable, we're cool
        if (is_readable($configfile)) {
            $yamlparser = new \Symfony\Component\Yaml\Parser();
            $this->config = $yamlparser->parse(file_get_contents($configfile) . "\n");
            return $this->config;
        }

        // Otherwise, check if there's a config.yml.dist
        if (is_readable($configdistfile)) {
            $yamlparser = new \Symfony\Component\Yaml\Parser();
            $this->config = $yamlparser->parse(file_get_contents($configdistfile) . "\n");

            // If config.yml.dist exists, attempt to copy it to config.yml.
            if (copy($configdistfile, $configfile)) {
                // Success!
                $this->app['log']->add("Copied 'extensions/" . $this->namespace . "/config.yml.dist' to 'extensions/" .
                    $this->namespace . "/config.yml'.", 2);
            } else {
                // Failure!!
                $message = "Couldn't copy 'extensions/" . $this->namespace . "/config.yml.dist' to 'extensions/" .
                    $this->namespace . "/config.yml': File is not writable. Create the file manually, or make the folder writable.";
                $this->app['log']->add($message, 3);
                $this->app['session']->getFlashBag()->set('error', $message);
            }

            return $this->config;
        }

        // Nope. No config.
        return false;

    }


    public function getName()
    {
        return $this->namespace;
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

    /**
     * Boilerplate for init(). Deprecated, use initialize instead.
     */
    public function init()
    {

    }

    /**
     * Boilerplate for initialize()
     */
    public function initialize()
    {
        // call deprecated function
        return $this->init();
    }

    /**
     * Return the available Twig Functions, override for \Twig_extension::getFunctions
     * @return array
     */
    public function getFunctions()
    {

        return $this->functionlist;

    }

    /**
     * Add a Twig Function
     *
     * @param string $name
     * @param string $callback
     */
    public function addTwigFunction($name, $callback)
    {

        $this->functionlist[] = new \Twig_SimpleFunction($name, array($this, $callback));

    }


    /**
     * Return the available Twig Filters, override for \Twig_extension::getFilters
     * @return array
     */
    public function getFilters()
    {

        return $this->filterlist;

    }

    /**
     * Add a Twig Filter
     *
     * @param string $name
     * @param string $callback
     */
    public function addTwigFilter($name, $callback)
    {

        $this->filterlist[] = new \Twig_SimpleFilter($name, array($this, $callback));

    }


    /**
     * Return the available Snippets, used in \Bolt\Extensions
     *
     * @return array
     */
    public function getSnippets()
    {
        return $this->snippetlist;
    }

    /**
     * Insert a snippet into the generated HTML.
     *
     * @param string $name
     * @param string $callback
     * @param string $var1
     * @param string $var2
     * @param string $var3
     */
    public function addSnippet($name, $callback, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->snippetlist[] = array($name, $callback, $this->namespace, $var1, $var2, $var3);
    }


    /**
     * Insert a snippet into the generated HTML. Deprecated, use addSnippet() instead.
     */
    public function insertSnippet($name, $callback, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->addSnippet($name, $callback, $var1, $var2, $var3);
    }


    /**
     * Make sure jQuery is added.
     */
    public function addJquery()
    {
        $this->app['extensions']->addJquery();
    }

    /**
     * Don't make sure jQuery is added. Note that this does not mean that jQuery will _not_ be added.
     * It only means that the extension will not add it, but others still might do so.
     */
    public function disableJquery()
    {
        $this->app['extensions']->disableJquery();
    }

    /**
     * Add a javascript file to the rendered HTML.
     *
     * @param string $filename
     */
    public function addJavascript($filename)
    {

        // check if the file is located relative to the current extension.
        if (file_exists($this->basepath."/".$filename)) {
            $this->app['extensions']->addJavascript($this->app['paths']['app'] . "extensions/" . $this->namespace . "/" . $filename);
        } else {
            $this->app['log']->add("Couldn't add Javascript '$filename': File does not exist in 'extensions/".$this->namespace."'.", 2);
        }

    }

    /**
     * Add a CSS file to the rendered HTML.
     *
     * @param string $filename
     */
    public function addCSS($filename) {

        // check if the file is located relative to the current extension.
        if (file_exists($this->basepath."/".$filename)) {
            $this->app['extensions']->addCss($this->app['paths']['app'] . "extensions/" . $this->namespace . "/" . $filename);
        } else {
            $this->app['log']->add("Couldn't add CSS '$filename': File does not exist in 'extensions/".$this->namespace."'.", 2);
        }

    }

    /**
     * Parse a snippet, an pass on the generated HTML to the caller (Extensions)
     *
     * @param string $callback
     * @param string $var1
     * @param string $var2
     * @param string $var3
     * @return bool|string
     */
    public function parseSnippet($callback, $var1 = "", $var2 = "", $var3 = "")
    {

        if (method_exists($this, $callback)) {
            return call_user_func(array($this, $callback), $var1, $var2, $var3);
        } else {
            return false;
        }

    }

    /**
     * Add/Insert a Widget (for instance, on the dashboard)
     *
     * @param string $type
     * @param string $location
     * @param string $callback
     * @param string $additionalhtml
     * @param bool $defer
     * @param int $cacheduration
     * @param string $var1
     * @param string $var2
     * @param string $var3
     * @internal param string $name
     */
    public function addWidget($type, $location, $callback, $additionalhtml = "", $defer = true, $cacheduration = 180, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->app['extensions']->insertWidget($type, $location, $callback, $this->namespace, $additionalhtml, $defer, $cacheduration, $var1, $var2, $var3);
    }


    /**
     * Deprecated function to Insert a Widget (for instance, on the dashboard). Use addWidget() instead.
     */
    public function insertWidget($type, $location, $callback, $additionalhtml = "", $defer = true, $cacheduration = 180, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->addWidget($type, $location, $callback, $additionalhtml, $defer, $cacheduration, $var1, $var2, $var3);
    }

    /**
     * Parse a widget, an pass on the generated HTML to the caller (Extensions)
     *
     * @param string $callback
     * @param string $var1
     * @param string $var2
     * @param string $var3
     * @return bool|string
     */
    public function parseWidget($callback, $var1 = "", $var2 = "", $var3 = "")
    {

        if (method_exists($this, $callback)) {
            return call_user_func(array($this, $callback), $var1, $var2, $var3);
        } else {
            return false;
        }

    }

}
