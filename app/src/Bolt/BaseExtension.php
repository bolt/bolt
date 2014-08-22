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

    private $configLoaded;

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

        // Don't load config just yet. Let 'Extensions' handle this when
        // activating, just clear the "configLoaded" flag to tell the
        // lazy-loading mechanism to do its thing.
        // $this->getConfig();
        $this->configLoaded = false;

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
    private function setBasepath()
    {
        $reflection = new \ReflectionClass($this);
        $this->basepath = dirname($reflection->getFileName());
        $this->namespace = basename(dirname($reflection->getFileName()));
    }

    /**
     * Get location of config files
     *
     * @return array
     */
    private function getConfigFiles()
    {
        $configfiles = array();

        $configfiles[] = $this->basepath . '/config.yml';
        $configfiles[] = $this->basepath . '/config_local.yml';

        if (BOLT_COMPOSER_INSTALLED) {
            $configfiles[] = BOLT_CONFIG_DIR . DIRECTORY_SEPARATOR . $this->namespace . '.yml';
            $configfiles[] = BOLT_CONFIG_DIR . DIRECTORY_SEPARATOR . $this->namespace . '_local.yml';
        }

        return $configfiles;
    }

    /**
     * Override this to provide a default configuration, which will be used
     * in the absence of a config.yml file.
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array();
    }

    /**
     * Load the configuration files, creating missing files as needed based on
     * the .dist default.
     *
     * @return array
     */
    public function getConfig()
    {
        if ($this->configLoaded) {
            return $this->config;
        }

        $this->config = $this->getDefaultConfig();
        foreach ($this->getConfigFiles() as $filename) {
            $this->loadConfigFile($filename);
        }
        $this->configLoaded = true;

        return $this->config;
    }

    private function loadConfigFile($configfile)
    {
        $configdistfile = "$configfile.dist";
        $yamlparser = new \Symfony\Component\Yaml\Parser();

        if (is_readable($configfile)) {
            // If it's readable, we're cool
            $new_config = $yamlparser->parse(file_get_contents($configfile) . "\n");

            // Don't error on empty config files
            if (is_array($new_config)) {
                $this->config = array_merge($this->config, $new_config);
            }
        } elseif (is_readable($configdistfile)) {
            // Otherwise, check if there's a config.yml.dist
            $new_config = $yamlparser->parse(file_get_contents($configdistfile) . "\n");

            // If config.yml.dist exists, attempt to copy it to config.yml.
            if (copy($configdistfile, $configfile)) {
                // Success!
                $this->app['log']->add(
                    "Copied $configdistfile to $configfile",
                    2
                );
                $this->config = array_merge($this->config, $new_config);
            } else {
                // Failure!!
                $configdir = dirname($configfile);
                $message = "Couldn't copy $configdistfile to $configfile: " .
                    "File is not writable. Create the file manually, or make " .
                    " the $configdir directory writable.";
                $this->app['log']->add($message, 3);
                $this->app['session']->getFlashBag()->set('error', $message);
            }
        }
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

        foreach ($this->getConfigFiles() as $configFile) {
            if (file_exists($configFile)) {
                $this->info['config'][] = array(
                    'file' => $configFile,
                    'writable' => is_writable($configFile)
                );
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
     * @param array $options
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
     * @param array $options
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
     * @param bool $late
     */
    public function addJavascript($filename, $late = false)
    {
        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addJavascript($this->getUrl('extensions') . "/" . $this->namespace . "/" . $filename, $late);
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
     * @param bool $late
     */
    public function addCSS($filename, $late = false)
    {
        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addCss($this->getUrl('extensions') . "/" . $this->namespace . "/" . $filename, $late);
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
     * @return bool
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
     * @return bool True if permission allowed
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
    public function parseWidget($callback, $var1 = '', $var2 = '', $var3 = '')
    {
        if (method_exists($this, $callback)) {
            return call_user_func(array($this, $callback), $var1, $var2, $var3);
        } else {
            return false;
        }
    }
}
