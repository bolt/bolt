<?php

namespace Bolt\Configuration;

use ArrayAccess;
use Bolt\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ConfigurationProxy a simple wrapper that allows passing a pointer to the eventual
 * compiled and validated configuration.
 * @internal
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigurationValueProxy implements ArrayAccess, EventSubscriberInterface
{

    protected $data;
    protected $config;
    protected $path;
    protected $default;
    protected $checked = false;

    public function __construct(Config $config, $path, $default = null)
    {
        $this->config = $config;
        $this->path = $path;
        $this->default = $default;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 34],
        ];
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset
     * An offset to check for.
     *
     * @return boolean true on success or false on failure.
     *
     * The return value will be cast to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        $this->initialize();

        return array_key_exists($offset, $this->data);
    }

    public function initialize()
    {
        if (!$this->checked) {
            $this->config->checkConfig();
            $this->checked = true;
            $this->data = $this->config->get($this->path, $this->default);
        }
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset
     * The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->data[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset
     * The offset to assign the value to.
     *
     * @param mixed $value
     * The value to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->data[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset
     * The offset to unset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->data[$offset]);
    }

    public function onKernelRequest()
    {
        $this->checked = false;
        $this->initialize();
    }
}
