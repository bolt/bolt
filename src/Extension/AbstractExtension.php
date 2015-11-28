<?php

namespace Bolt\Extension;

use Bolt\Helpers\Str;
use Silex\Application;

/**
 * Defined some base functionality for extensions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /** @var Application */
    protected $app;
    /** @var string */
    protected $path;
    /** @var string */
    protected $name;
    /** @var string */
    protected $namespace;

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * Return the application object.
     *
     * Note: This is allows traits to access app without losing coding completion
     *
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * {@inheritdoc}
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        if ($this->name === null) {
            $name = get_class($this);
            $pos = strrpos($name, '\\');
            $this->name = $pos === false ? $name : substr($name, $pos + 1);
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        if ($this->namespace === null) {
            $class = get_class($this);
            $class = Str::replaceFirst('Bolt\\Extension\\', '', $class);
            $this->namespace = substr($class, 0, strrpos($class, '\\'));
        }

        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if ($this->path === null) {
            $reflected = new \ReflectionObject($this);
            $this->path = dirname($reflected->getFileName());
        }

        return $this->path;
    }
}
