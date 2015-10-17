<?php
namespace Bolt\Asset\Widget;

use Bolt\Asset\AssetInterface;

/**
 * Widget objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Widget implements AssetInterface, \ArrayAccess
{
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

    /**
     * Constructor.
     *
     * @param array $options
     *
     * Options array should contain the following elements:
     *   - 'type'              (string)  Either 'frontend' or 'backend'
     *   - 'location'          (string)  Target locational element
     *   - 'callback'          (string)  Callback function name
     *   - 'callbackarguments' (array)   Arguements to pass to callback
     *   - 'content'           (string)  HTML content to inject
     *   - 'class'             (string)  CSS class(es) to use
     *   - 'prefix'            (string)  HTML to add before the widget
     *   - 'postfix'           (string)  HTML to add after the widget
     *   - 'defer'             (boolean) True means rendering of the widget is done in a seperate request
     *   - 'priority'          (integer) Priotrity in the render queue
     *   - 'cacheduration'     (integer) Number of seconds to cache the widget
     */
    public function __construct(array $options)
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $this->checkOptions($options);

        $this->type = $options['type'];
        $this->location = $options['location'];
        $this->callback = $options['callback'];
        $this->callbackArguments = $options['callbackarguments'];
        $this->content = $options['content'];
        $this->class = $options['class'];
        $this->prefix = $options['prefix'];
        $this->postfix = $options['postfix'];
        $this->defer = $options['defer'];
        $this->priority = $options['priority'];
        $this->cacheDuration = $options['cacheduration'];
    }

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
    public function getType()
    {
        return $this->type;
    }

    /**
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
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
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
    public function getDefer()
    {
        return (boolean) $this->defer;
    }

    /**
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
        return (integer) $this->cacheDuration;
    }

    /**
     * @param integer $cacheDuration
     *
     * @return Widget
     */
    public function setCacheDuration($cacheDuration)
    {
        $this->cacheDuration = (integer) $cacheDuration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_NOTICE);
            return '';
        }
    }

    /**
     * Either return the passed in 'content' or the result of the callback.
     *
     * @return string
     */
    protected function toString()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        return call_user_func_array($this->callback, $this->callbackArguments);
    }

    /**
     * @param array $options
     *
     * @throws InvalidArgumentException
     */
    protected function checkOptions(array $options)
    {
        if ($options['type'] === null || !in_array($options['type'], ['frontend', 'backend'])) {
            throw new \InvalidArgumentException("Invalid widget parameters, 'type' must be either 'frontend' or 'backend'.");
        }
        if ($options['location'] === null) {
            throw new \InvalidArgumentException("Invalid widget parameters, 'location' required.");
        }
        if ($options['content'] === null && $options['callback'] === null) {
            throw new \InvalidArgumentException("Invalid widget parameters, must specify either 'content' or 'callback'.");
        }
        if ($options['callbackarguments'] !== null && !is_array($options['callbackarguments'])) {
            throw new \InvalidArgumentException("Invalid widget parameters, 'callbackarguments' must be of type array or null.");
        }
    }

    /**
     * @return array
     */
    protected function getDefaultOptions()
    {
        return [
            'type'              => null,
            'location'          => null,
            'callback'          => null,
            'callbackarguments' => null,
            'content'           => null,
            'class'             => null,
            'prefix'            => null,
            'postfix'           => null,
            'defer'             => true,
            'priority'          => 0,
            'cacheduration'     => 3600
        ];
    }
}
