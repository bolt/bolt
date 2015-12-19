<?php

namespace Bolt\Extension;

use Silex\Application;

/**
 * Management class for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var \Bolt\Composer\ExtensionAutoloader */
    protected $autoloader;
    /** @var ExtensionInterface[] */
    protected $extensions;

    /** @var Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->autoloader = $app['extensions.autoload'];
    }

    /**
     * Load and fetch the extension classes.
     */
    public function load()
    {
        $this->extensions = $this->autoloader->load();
    }

    /**
     * Get an installed extension class.
     *
     * @param $name
     *
     * @return ExtensionInterface
     */
    public function get($name)
    {
        if (isset($this->extensions[$name])) {
            return $this->extensions[$name];
        }
    }
}
