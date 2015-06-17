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
        return $this->getCallableResult();
    }

    /**
     * Get the output from the callback.
     *
     * @return string
     */
    private function getCallableResult()
    {
        if ($this->isCore() && is_callable($this->callback)) {
            // Snippet is a callback in the 'global scope'
            return call_user_func_array($this->callback, (array) $this->parameters);
        } elseif ($callable = $this->getExtensionCallable()) {
            // Snippet is defined in the extension itself.
            return call_user_func_array($callable, (array) $this->parameters);
        } elseif (is_string($this->callback)) {
            // Insert the 'callback' as a string.
            return $this->callback;
        }

        return '';
    }

    /**
     * Check for an enabled extension with a valid snippet callback.
     *
     * @return callable|null
     */
    private function getExtensionCallable()
    {
        if (is_callable($this->callback)) {
            return $this->callback;
        } elseif (is_callable([$this->extension, $this->callback])) {
            return [$this->extension, $this->callback];
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
