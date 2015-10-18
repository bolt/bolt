<?php
namespace Bolt\Asset\Snippet;

use Bolt\Asset\AssetInterface;

/**
 * Snippet objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Snippet implements AssetInterface
{
    /** @var integer */
    protected $priority;
    /** @var string */
    protected $location;
    /** @var callable|string */
    protected $callback;
    /** @var array */
    protected $callbackArguments;
    /** @var string */
    protected $extension = 'core';

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the priority.
     *
     * @param integer $priority
     *
     * @return Snippet
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set location.
     *
     * @param string $location
     *
     * @return Snippet
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get callback or HTML string.
     *
     * @return callable|string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set callback or HTML string.
     *
     * @param callable|string $callback
     *
     * @return Snippet
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the callback arguments.
     *
     * @return array
     */
    public function getCallbackArguments()
    {
        return (array) $this->callbackArguments;
    }

    /**
     * Set the callback arguments.
     *
     * @param array $callbackArguments
     *
     * @return Snippet
     */
    public function setCallbackArguments($callbackArguments)
    {
        $this->callbackArguments = $callbackArguments;

        return $this;
    }

    /**
     * Get the extension name that this connects to.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set the extension name that this connects to.
     *
     * @param string $extensionName
     *
     * @return Snippet
     */
    public function setExtension($extensionName)
    {
        $this->extension = $extensionName;

        return $this;
    }

    /**
     * Check if the snippet is for core or an extension.
     *
     * @return boolean
     */
    public function isCore()
    {
        return $this->extension === 'core';
    }
}
