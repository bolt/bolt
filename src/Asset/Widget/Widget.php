<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\CallableInvokerTrait;

/**
 * Widget objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Widget implements WidgetAssetInterface
{
    use CallableInvokerTrait;

    /** @var string */
    protected $key;
    /** @var string */
    protected $zone;
    /** @var string */
    protected $location;
    /** @var callable */
    protected $callback;
    /** @var array */
    protected $callbackArguments;
    /** @var string */
    protected $content;
    /** @var array */
    protected $class;
    /** @var string */
    protected $prefix;
    /** @var string */
    protected $postfix;
    /** @var boolean */
    protected $defer;
    /** @var integer */
    protected $priority;
    /** @var integer */
    protected $cacheDuration;

    /** @var string */
    private $rendered;

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey()
    {
        if ($this->key === null) {
            $this->key = md5(json_encode(get_object_vars($this)));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * {@inheritdoc}
     */
    public function setZone($zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * {@inheritdoc}
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallbackArguments()
    {
        return $this->callbackArguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setCallbackArguments(array $callbackArguments)
    {
        $this->callbackArguments = $callbackArguments;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass($class)
    {
        if (is_array($class)) {
            foreach ($class as $classitem) {
                $this->setClass($classitem);
            }

            return $this;
        }

        if (substr($class, 0, 7) != 'widget-') {
            $class = 'widget-' . $class;
        }

        $this->class[] = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPostfix()
    {
        return $this->postfix;
    }

    /**
     * {@inheritdoc}
     */
    public function setPostfix($postfix)
    {
        $this->postfix = $postfix;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeferred()
    {
        return (boolean) $this->defer;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefer($defer)
    {
        $this->defer = (boolean) $defer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return (integer) $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = (integer) $priority;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheDuration($cacheDuration)
    {
        $this->cacheDuration = $cacheDuration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->toString();
    }

    /**
     * Either return the passed in 'content' or the result of the callback.
     *
     * @return string
     */
    protected function toString()
    {
        if ($this->rendered !== null) {
            return $this->rendered;
        }

        if (is_callable($this->callback)) {
            try {
                return $this->rendered = $this->invokeCallable($this->callback, $this->callbackArguments);
            } catch (\Exception $e) {
                trigger_error($e->getMessage(), E_USER_NOTICE);
            }
        }

        return $this->content ?: '';
    }
}
