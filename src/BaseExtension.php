<?php
namespace Bolt;

use Bolt\Extensions\ExtensionInterface;
use Symfony\Component\Console\Command\Command;
use Composer\Json\JsonFile;
use utilphp\util;

abstract class BaseExtension extends \Twig_Extension implements ExtensionInterface
{
    protected $app;
    protected $basepath;
    protected $namespace;
    protected $functionlist;
    protected $filterlist;
    protected $snippetlist;

    private $extensionConfig;
    private $composerJsonLoaded;
    private $composerJson;
    private $configLoaded;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->setBasepath();

        // Don't load config just yet. Let 'Extensions' handle this when
        // activating, just clear the "configLoaded" flag to tell the
        // lazy-loading mechanism to do its thing.
        $this->configLoaded = false;
        $this->extensionConfig = null;
        $this->composerJsonLoaded = false;

        $this->functionlist = array();
        $this->filterlist = array();
        $this->snippetlist = array();
    }

    /**
     * Set the 'basepath' and the 'namespace' for the extension. We can't use
     * __DIR__, because that would give us the base path for BaseExtension.php
     * (the file you're looking at), rather than the base path for the actual,
     * derived, extension class.
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
     * Get the base path, that is, the directory where the (derived) extension
     * class file is located. The base path is the "root directory" under which
     * all files related to the extension can be found.
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basepath;
    }

    public function getBaseUrl()
    {
        $relative = str_replace($this->app['resources']->getPath('extensions'), "", $this->basepath);

        return $this->app['resources']->getUrl('extensions') . ltrim($relative, "/") . "/";
    }

    /**
     * Gets the Composer name, e.g. 'bolt/foobar-extension'.
     * @return string The Composer name for this extension, or NULL if the
     *                extension is not composerized.
     */
    public function getComposerName()
    {
        $composerjson = $this->getComposerJSON();
        if (isset($composerjson['name'])) {
            return $composerjson['name'];
        } else {
            return null;
        }
    }

    /**
     * Gets a 'machine name' for this extension.
     * The machine name is the composer name, if available, or a slugified
     * version of the name as reported by getName() otherwise.
     *
     * @return string
     */
    public function getMachineName()
    {
        $composerName = $this->getComposerName();
        if (empty($composerName)) {
            return util::slugify($this->getName());
        } else {
            return $composerName;
        }
    }

    /**
     * Get the contents of the extension's composer.json file, lazy-loading
     * as needed.
     */
    private function getComposerJSON()
    {
        if (!$this->composerJsonLoaded) {
            $this->composerJsonLoaded = true;
            $this->composerJson = null;
            $jsonFile = new JsonFile($this->getBasepath() . '/composer.json');
            if ($jsonFile->exists()) {
                $this->composerJson = $jsonFile->read();
            }
        }

        return $this->composerJson;
    }

    /**
     * Builds an array suitable for conversion to JSON, which in turn will end
     * up in a consolidated JSON file containing the configurations of all
     * installed extensions.
     */
    public function getExtensionConfig()
    {
        $composerjson = $this->getComposerJSON();
        if (!is_array($this->extensionConfig)) {
            $composerjson = $this->getComposerJSON();
            if (is_array($composerjson)) {
                $this->extensionConfig = array(strtolower($composerjson['name']) => array(
                    'name' => $this->getName(),
                    'json' => $composerjson
                ));
            } else {
                $this->extensionConfig = array(
                    $this->getName() => array(
                        'name' => $this->getName(),
                        'json' => array()
                    )
                );
            }
        }

        return $this->extensionConfig;
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

        // Config file name should follow the format of {ext_name}.{vendor}.yml
        // and be in the root of the extension config directory
        $basefile = explode('/', $this->getMachineName());
        $basefile = isset($basefile[1]) ? $basefile[1] . '.' . $basefile[0] : $basefile[0];
        $basefile = $this->app['resources']->getPath('extensionsconfig') . '/' . $basefile;

        // Load main config
        if ($this->isConfigValid($basefile . '.yml', true)) {
            $this->loadConfigFile($basefile . '.yml');
        }

        // Load local config
        if ($this->isConfigValid($basefile . '_local.yml', false)) {
            $this->loadConfigFile($basefile . '.yml');
        }

        $this->configLoaded = true;

        return $this->config;
    }

    /**
     * Test if a given config file is valid (exists and is readable) and create
     * if required.
     *
     * @param  string  $configfile Fully qualified file path
     * @param  boolean $create     True - create file is non-existant
     *                             False - Only test for file existance
     * @return boolean
     */
    private function isConfigValid($configfile, $create)
    {
        if (file_exists($configfile)) {
            if (is_readable($configfile)) {
                return true;
            } else {
                // Config file exists but is not readable
                $configdir = dirname($configfile);
                $message = "Couldn't read $configfile. Please correct file " .
                           "permissions and ensure the $configdir directory readable.";
                $this->app['log']->add($message, 3);
                $this->app['session']->getFlashBag()->set('error', $message);

                return false;
            }
        } elseif ($create) {
            $configdistfile = $this->basepath . '/config.yml.dist';

            // There are cases where the config directory may not exist yet.
            // Firstly we try to create it.
            if (!is_dir(dirname($configfile))) {
                @mkdir(dirname($configfile), 0777, true);
            }

            // If config.yml.dist exists, attempt to copy it to config.yml.
            if (is_readable($configdistfile) && is_dir(dirname($configfile))) {
                if (copy($configdistfile, $configfile)) {
                    // Success!
                    $this->app['log']->add(
                        "Copied $configdistfile to $configfile",
                        2
                    );

                    return true;
                } else {
                    // Failure!!
                    $configdir = dirname($configfile);
                    $message = "Couldn't copy $configdistfile to $configfile: " .
                               "File is not writable. Create the file manually, " .
                               "or make the $configdir directory writable.";
                    $this->app['log']->add($message, 3);
                    $this->app['session']->getFlashBag()->set('error', $message);

                    return false;
                }
            }

            return false;
        }
    }

    /**
     * Load and process a give config file
     *
     * @param string $configfile Fully qualified file path
     */
    private function loadConfigFile($configfile)
    {
        $yamlparser = new \Symfony\Component\Yaml\Parser();

        $new_config = $yamlparser->parse(file_get_contents($configfile) . "\n");

        // Don't error on empty config files
        if (is_array($new_config)) {
            $this->config = array_merge_recursive_distinct($this->config, $new_config);
        }
    }

    public function getName()
    {
        return $this->namespace;
    }

    /**
     * Hook method that gets called during the process of registering
     * extensions with Bolt's core.
     * The `initialize()` method is called after constructing the extension
     * and loading its configuration, but before dispatching into any of its
     * route handlers, and before hooking up Twig functions and filters.
     * This means that `$this->app` and `$this->config` are available, but you
     * cannot rely on anything that the extension itself injects into Bolt, and
     * you cannot safely access any other extensions.
     *
     * Typical things to do in `initialize()` include:
     * - registering CSS and JavaScript files to be included in frontend
     *   responses
     * - registering Twig functions and filters
     * - registering providers into Bolt's DI hub ($app)
     * - setting up internal state that relies on `$this->config`
     * - registering route handlers
     * - extending the menu
     *
     * An empty default implementation is given for convenience.
     */
    public function initialize()
    {
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
     * @param array  $options
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
     * @param array  $options
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
        $this->app['extensions']->insertSnippet($name, $callback, $this->getName(), $var1, $var2, $var3);
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
     * Returns a list of all css and js assets that are added via extensions.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this->app['extensions']->getAssets();
    }

    /**
     * Add a javascript file to the rendered HTML.
     *
     * @param string $filename
     * @param bool   $late
     */
    public function addJavascript($filename, $late = false)
    {
        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addJavascript($this->getBaseUrl() . $filename, $late);
        } elseif (file_exists($this->app['paths']['themepath'] . "/" . $filename)) {
            // file is located relative to the theme path.
            $this->app['extensions']->addJavascript($this->app['paths']['theme'] . $filename, $late);
        } else {
            // Nope, can't add the CSS..
            $this->app['log']->add("Couldn't add Javascript '$filename': File does not exist in '" . $this->getBaseUrl() . "'.", 2);
        }
    }

    /**
     * Add a CSS file to the rendered HTML.
     *
     * @param string $filename
     * @param bool   $late
     */
    public function addCSS($filename, $late = false)
    {
        // check if the file exists.
        if (file_exists($this->basepath . "/" . $filename)) {
            // file is located relative to the current extension.
            $this->app['extensions']->addCss($this->getBaseUrl() . $filename, $late);
        } elseif (file_exists($this->app['paths']['themepath'] . "/" . $filename)) {
            // file is located relative to the theme path.
            $this->app['extensions']->addCss($this->app['paths']['theme'] . $filename, $late);
        } else {
            // Nope, can't add the CSS..
            $this->app['log']->add("Couldn't add CSS '$filename': File does not exist in '" . $this->getBaseUrl() . "'.", 2);
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
        $this->app['extensions']->insertWidget($type, $location, $callback, $this->getName(), $additionalhtml, $defer, $cacheduration, $var1, $var2, $var3);
    }

    /**
     * Deprecated
     *
     * @see: requireUserRole()
     *
     * @param  string $permission
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
     * @param  string $permission
     * @return bool   True if permission allowed
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

    /**
     * Add a console command
     *
     * @param Command $command
     */
    public function addConsoleCommand(Command $command)
    {
        $this->app['console']->add($command);
    }
}
