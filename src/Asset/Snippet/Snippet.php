<?php
namespace Bolt\Asset\Snippet;

/**
 * Snippet objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Snippet implements SnippetAssetInterface
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
        return (string) $this->callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

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
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallbackArguments()
    {
        return (array) $this->callbackArguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setCallbackArguments($callbackArguments)
    {
        $this->callbackArguments = $callbackArguments;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * {@inheritdoc}
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
