<?php
namespace Bolt\Assets\Snippets;

/**
 * Snippet objects.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
class Snippet
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
     * @param array           $parameters
     */
    public function __construct($location, $callback, $extension = 'core', array $parameters = [])
    {
        $this->location   = $location;
        $this->callback   = $callback;
        $this->extension  = $extension;
        $this->parameters = $parameters;
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
