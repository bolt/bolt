<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\CallableInvokerTrait;

/**
 * Widget objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Widget implements AssetInterface, \ArrayAccess
{
    use CallableInvokerTrait;

    /** @var string */
    protected $key;
    /** @var string */
    protected $type;
    /** @var string */
    protected $location;
    /** @var callable */
    protected $callback;
    /** @var array */
    protected $callbackArguments;
    /** @var string */
    protected $content;
    /** @var string */
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
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the widget (semi-) unique key.
     *
     * @internal
     *
     * @return Widget
     */
    public function setKey()
    {
        if ($this->key === null) {
            $this->key = md5(json_encode(get_object_vars($this)));
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the widget type, either 'frontend' or 'backend'.
     *
     * @param string $type
     *
     * @return Widget
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Target locational element.
     *
     * @param string $location
     *
     * @return Widget
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * callable|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Callback function name.
     *
     * @param callable $callback
     *
     * @return Widget
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getCallbackArguments()
    {
        return $this->callbackArguments;
    }

    /**
     * Arguments to pass to callback.
     *
     * @param array $callbackArguments
     *
     * @return Widget
     */
    public function setCallbackArguments(array $callbackArguments)
    {
        $this->callbackArguments = $callbackArguments;

        return $this;
    }

    /**
     * Get the content for the widget.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Raw HTML content to inject.
     *
     * @param string $content
     *
     * @return Widget
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * CSS class(es) to use.
     *
     * @param string $class
     *
     * @return Widget
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * HTML to add before the widget.
     *
     * @param string $prefix
     *
     * @return Widget
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostfix()
    {
        return $this->postfix;
    }

    /**
     * HTML to add after the widget.
     *
     * @param string $postfix
     *
     * @return Widget
     */
    public function setPostfix($postfix)
    {
        $this->postfix = $postfix;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeferred()
    {
        return (boolean) $this->defer;
    }

    /**
     * Setting to 'true' means rendering of the widget is done in a separate
     * request.
     *
     * @param boolean $defer
     *
     * @return Widget
     */
    public function setDefer($defer)
    {
        $this->defer = (boolean) $defer;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPriority()
    {
        return (integer) $this->priority;
    }

    /**
     * Priority in the render queue.
     *
     * @param integer $priority
     *
     * @return Widget
     */
    public function setPriority($priority)
    {
        $this->priority = (integer) $priority;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }

    /**
     * Number of seconds to cache the widget.
     *
     * @param integer $cacheDuration
     *
     * @return Widget
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
