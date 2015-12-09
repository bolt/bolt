<?php

namespace Bolt\Extension;

use Bolt\Helpers\Str;
use Pimple as Container;
use Silex\Application;

/**
 * Defined some base functionality for extensions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /** @var Container */
    protected $container;
    /** @var string */
    protected $path;
    /** @var string */
    protected $name;
    /** @var string */
    protected $vendor;
    /** @var string */
    protected $namespace;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
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
    public function getVendor()
    {
        if ($this->vendor === null) {
            $namespace = $this->getNamespace();
            $name = Str::replaceFirst('Bolt\\Extension\\', '', $namespace);
            $pos = strpos($name, '\\');
            $this->vendor = $pos === false ? $name : substr($name, 0, $pos);
        }

        return $this->vendor;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        if ($this->namespace === null) {
            $class = get_class($this);
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

    /**
     * Return the container.
     *
     * Note: This is allows traits to access app without losing coding completion
     *
     * @return Container
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
