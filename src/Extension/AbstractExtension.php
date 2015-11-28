<?php

namespace Bolt\Extension;

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

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
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
        $class = get_class($this);
        return substr($class, 0, strrpos($class, '\\'));
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
