<?php

namespace Bolt\Asset\Snippet;

use Bolt\Controller\Zone;

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
    /** @var string */
    protected $zone = Zone::FRONTEND;

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            return (string) $this->getCallableResult();
        } catch (\Exception $e) {
            return '<!-- An exception occurred creating snippet -->';
        }
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
     * Get the output from the callback.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    private function getCallableResult()
    {
        if ($this->extension === 'core' && is_callable($this->callback)) {
            // Snippet is a callback in the 'global scope'
            return call_user_func_array($this->callback, (array) $this->callbackArguments);
        } elseif ($callable = $this->getCallable()) {
            // Snippet is defined in the extension itself.
            return call_user_func_array($callable, (array) $this->callbackArguments);
        } elseif (is_string($this->callback) || $this->callback instanceof \Twig_Markup) {
            // Insert the 'callback' as a string.
            return (string) $this->callback;
        }

        try {
            $msg = sprintf('Snippet loading failed for %s with callable %s', $this->extension, serialize($this->callback));
        } catch (\Exception $e) {
            $msg = sprintf('Snippet loading failed for %s with an unknown callback.', $this->extension);
        }

        throw new \RuntimeException($msg);
    }

    /**
     * Check for a valid snippet callback.
     *
     * @return callable|null
     */
    private function getCallable()
    {
        if (is_callable($this->callback)) {
            return $this->callback;
        } elseif (is_callable([$this->extension, $this->callback])) {
            return [$this->extension, $this->callback];
        }
    }
}
