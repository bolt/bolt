<?php
namespace Bolt\Configuration\Type;

use Silex\Application;

/**
 * An interface to define how "place holder" objects can be resolved to their actual values
 */
interface ResolvableInterface
{
    /**
     * Resolves the value and returns it
     *
     * @param Application $app
     *
     * @return mixed
     */
    public function resolve(Application $app);

    /**
     * Updates the place holder value
     *
     * @param mixed $value
     */
    public function update($value);
}
