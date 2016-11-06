<?php

namespace Bolt\Configuration;

use ArrayAccess;
use Bolt\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ConfigurationProxy a simple wrapper that allows passing a pointer to the eventual
 * compiled and validated configuration.
 *
 * @internal
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigurationValueProxy implements ArrayAccess, EventSubscriberInterface
{
    /** @var mixed|null */
    protected $data;
    /** @var Config */
    protected $config;
    /** @var string */
    protected $path;
    /** @var mixed|null */
    protected $default;
    /** @var bool */
    protected $checked = false;

    /**
     * Constructor.
     *
     * @param Config $config
     * @param string $path
     * @param mixed  $default
     */
    public function __construct(Config $config, $path, $default = null)
    {
        $this->config = $config;
        $this->path = $path;
        $this->default = $default;
    }

    /**
     *{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 34],
        ];
    }

    /**
     * Get the data of the loaded config
     *
     * @return array
     */
    public function getData()
    {
        $this->initialize();

        return $this->data;
    }

    /**
     *{@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->initialize();

        return array_key_exists($offset, $this->data);
    }

    /**
     * Initialize the configuration value.
     */
    public function initialize()
    {
        if (!$this->checked) {
            $this->config->checkConfig();
            $this->checked = true;
            $this->data = $this->config->get($this->path, $this->default);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     *{@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->data[$offset]);
    }

    /**
     * Kernel request event callback.
     */
    public function onKernelRequest()
    {
        $this->checked = false;
        $this->initialize();
    }
}
