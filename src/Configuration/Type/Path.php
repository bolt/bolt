<?php
namespace Bolt\Configuration\Type;

use Silex\Application;

/**
 * This class represents a place holder for a filesystem path
 */
class Path implements ResolvableInterface
{
    /** @var string */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Resolves the path from {@see ResourceManager::getPath}
     *
     * @param Application $app
     *
     * @return string
     */
    public function resolve(Application $app)
    {
        return $app['resources']->getPath($this->path);
    }

    /**
     * Update the path place holder.
     *
     * @param string $path
     */
    public function update($path)
    {
        $this->path = $path;
    }
}
