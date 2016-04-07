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
        if (is_callable($this->callback)) {
            return call_user_func_array($this->callback, (array) $this->callbackArguments);
        } elseif (is_string($this->callback) || $this->callback instanceof \Twig_Markup) {
            // Insert the 'callback' as a string.
            return (string) $this->callback;
        }

        try {
            $msg = sprintf('Snippet loading failed with callable %s', serialize($this->callback));
        } catch (\Exception $e) {
            $msg = sprintf('Snippet loading failed with an unknown callback.');
        }

        throw new \RuntimeException($msg);
    }
}
