<?php
namespace Bolt\Configuration\Type;

use Silex\Application;

/**
 * This class represents a place holder for a url
 */
class Url implements ResolvableInterface
{
    /** @var string ResourceManager path value */
    protected $prefix;

    /** @var string The path to append to the prefix value or absolute path */
    protected $path;

    /**
     * @param string $prefix
     * @param string $path
     */
    public function __construct($prefix, $path)
    {
        $this->prefix = $prefix;
        $this->path = $path;
    }

    /**
     * Resolves the prefix from {@see ResourceManager::getUrl} and appends the path.
     * If prefix is false, the path is assumed absolute.
     *
     * @param Application $app
     *
     * @return string
     */
    public function resolve(Application $app)
    {
        if ($this->prefix === false) {
            return $this->path;
        }
        return $app['resources']->getUrl($this->prefix) . $this->path;
    }

    /**
     * Updates the url place holder. If no prefix is specified
     * the new path is assumed to be absolute.
     *
     * @param string      $path
     * @param string|bool $prefix
     */
    public function update($path, $prefix = false)
    {
        $this->path = $path;
        $this->prefix = $prefix;
    }
}
