<?php

namespace Bolt;

use Bolt\Extensions\BaseExtensionInterface;

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
     * Get location of config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        $configfile = $this->basepath . '/config.yml';

        if(BOLT_COMPOSER_INSTALLED && file_exists(BOLT_CONFIG_DIR . DIRECTORY_SEPARATOR . $this->namespace . '.yml'))
        {
            $configfile = BOLT_CONFIG_DIR . DIRECTORY_SEPARATOR . $this->namespace . '.yml';
        }
        return $configfile;
    }

    /**
     * Get the config file. If it doesn't exist, attempt to fall back to config.yml.dist,
     * and rename it to config.yml.
     *
     * @return array
     */
    public function getConfig()
    {
        $configfile = $this->getConfigFile();
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
                $this->app['log']->add(
                    "Copied 'extensions/" . $this->namespace . "/config.yml.dist' to 'extensions/" .$this->namespace . "/config.yml'.",
                    2
                );
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

        $configFile = $this->getConfigFile();
        if (file_exists($configFile)) {
            if (BOLT_COMPOSER_INSTALLED && strpos($configFile, BOLT_CONFIG_DIR) === 0)
            {
                $this->info['config'] = "app/config/" . $this->namespace . ".yml";
            }
            else
            {
                $this->info['config'] = "app/extensions/" . $this->namespace . "/config.yml";
            }
            if (is_writable($configFile)) {
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
    public function addTwigFunction($name, $callback, $options = array())
    {

        $this->functionlist[] = new \Twig_SimpleFunction($name, array($this, $callback), $options);

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
    public function addTwigFilter($name, $callback, $options = array())
    {

        $this->filterlist[] = new \Twig_SimpleFilter($name, array($this, $callback), $options);

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
        $this->app['extensions']->insertSnippet($name, $callback, $this->namespace, $var1, $var2, $var3);
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
    public function addJavascript($filename, $late = false)
    {

        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addJavascript($this->app['paths']['app'] . "extensions/" . $this->namespace . "/" . $filename, $late);
        } elseif (file_exists($this->app['paths']['themepath'] . "/" . $filename)) {
            // file is located relative to the theme path.
            $this->app['extensions']->addJavascript($this->app['paths']['theme'] . $filename, $late);
        } else {
            // Nope, can't add the CSS..
            $this->app['log']->add("Couldn't add Javascript '$filename': File does not exist in 'extensions/".$this->namespace."'.", 2);
        }

    }

    /**
     * Add a CSS file to the rendered HTML.
     *
     * @param string $filename
     */
    public function addCSS($filename, $late = false)
    {
        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addCss($this->app['paths']['app'] . "extensions/" . $this->namespace . "/" . $filename, $late);
        } elseif (file_exists($this->app['paths']['themepath'] . "/" . $filename)) {
            // file is located relative to the theme path.
            $this->app['extensions']->addCss($this->app['paths']['theme'] . $filename, $late);
        } else {
            // Nope, can't add the CSS..
            $this->app['log']->add("Couldn't add CSS '$filename': File does not exist in 'extensions/".$this->namespace."'.", 2);
        }

    }

    /**
     * Add a menu option to the 'settings' menu. Note that the item is only added if the current user
     * meets the required permission.
     *
     * @see \Bolt\Extensions\addMenuOption()
     *
     * @param string $label
     * @param string $path
     * @param bool   $icon
     * @param string $requiredPermission (NULL if no permission is required)
     */
    public function addMenuOption($label, $path, $icon = false, $requiredPermission = null)
    {
        $this->app['extensions']->addMenuOption($label, $path, $icon, $requiredPermission);
    }

    /**
     * Check if there are additional menu-options set for the current user.
     *
     * @see \Bolt\Extensions\hasMenuOptions()
     */
    public function hasMenuOptions()
    {
        return $this->app['extensions']->hasMenuOption();
    }

    /**
     * Get an array with the additional menu-options that are set for the current user.
     *
     * @see \Bolt\Extensions\hasMenuOptions()
     */
    public function getMenuOptions()
    {
        return $this->app['extensions']->getMenuOption();
    }


    /**
     * Parse a snippet, an pass on the generated HTML to the caller (Extensions)
     *
     * @param  string      $callback
     * @param  string      $var1
     * @param  string      $var2
     * @param  string      $var3
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
     * @param mixed  $callback
     * @param string $additionalhtml
     * @param bool   $defer
     * @param int    $cacheduration
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
     * Deprecated
     *
     * @see: requireUserRole()
     *
     * @param string $permission
     */
    public function requireUserLevel($permission = 'dashboard')
    {
        return $this->requireUserPermission($permission);
    }


    /**
     * Check if a user is logged in, and has the proper required permission. If
     * not, we redirect the user to the dashboard.
     *
     * @param string $permission
     */
    public function requireUserPermission($permission = 'dashboard')
    {
        if ($this->app['users']->isAllowed($permission)) {
            return true;
        } else {
            simpleredirect($this->app['config']->get('general/branding/path'));
            return false;
        }
    }


    /**
     * Parse a widget, an pass on the generated HTML to the caller (Extensions)
     *
     * @param  string      $callback
     * @param  string      $var1
     * @param  string      $var2
     * @param  string      $var3
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
