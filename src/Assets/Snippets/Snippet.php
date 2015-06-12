<?php
namespace Bolt\Assets\Snippets;

use Bolt\Assets\AssetInterface;

/**
 * Snippet objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Snippet implements AssetInterface
{
    /** @var string */
    protected $location;
    /** @var callable|string */
    protected $callback;
    /** @var string */
    protected $extension;
    /** @var array */
    protected $parameters;

    /**
     * Constructor.
     *
     * @param string          $location
     * @param callable|string $callback
     * @param string          $extension
     * @param array|string    $parameters
     */
    public function __construct($location, $callback, $extension = 'core', array $parameters = [])
    {
        $this->location   = $location;
        $this->callback   = $callback;
        $this->extension  = $extension;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if ($this->isCore() && is_callable($this->getCallback())) {
            // @TODO FIXME
$app = \Bolt\Configuration\ResourceManager::getApp();
            // Snippet is a callback in the 'global scope'
            return call_user_func($this->getCallback(), $app, $this->getParameters());
        } elseif ($callable = $this->getExtensionCallable()) {
            // Snippet is defined in the extension itself.
            return call_user_func_array($callable, (array) $this->getParameters());
        } elseif (is_string($this->getCallback())) {
            // Insert the 'callback' as a string.
            return $this->getCallback();
        }
    }

    /**
     * Check for an enabled extension with a valid snippet callback.
     *
     * @return callable|null
     */
    private function getExtensionCallable()
    {
        if (is_callable($this->getCallback())) {
            return $this->getCallback();
        } elseif (is_callable([$this->getExtension(), $this->getCallback()])) {
            return [$this->getExtension(), $this->getCallback()];
        }
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
     * Get callback or HTML string.
     *
     * @return callable|string
     */
    public function getCallback()
    {
        return $this->callback;
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
     * Get the callback parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
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
