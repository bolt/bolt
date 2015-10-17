<?php
namespace Bolt\Asset;

use Bolt\Helpers\Arr;

/**
 * Trait for handling callables.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait CallableInvokerTrait
{
    /**
     * Get the return value from the callable.
     *
     * Parameters can be can handled in three ways:
     *   - null              - Nothing passed to the callback
     *   - Indexed array     - Value of each element will be passed to function in order
     *   - Associative array - Key names will attempt to match to the callable function variable names
     */
    protected function invokeCallable(callable $callback, $callbackArguments)
    {
        if ($callbackArguments === null) {
            return call_user_func($callback);
        }

        if (Arr::isIndexedArray($callbackArguments)) {
            return call_user_func_array($callback, (array) $callbackArguments);
        }

        $orderedArgs = $this->getArguments($callback, $callbackArguments);

        return call_user_func_array($callback, $orderedArgs);
    }

    /**
     * Get an ordered list of arguments.
     *
     * @param callable $callback
     * @param array    $callbackArguments
     *
     * @return array
     */
    private function getArguments(callable $callback, array $callbackArguments)
    {
        $parameters = $this->getParameters($callback);
        $arguments = [];
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $callbackArguments)) {
                $arguments[] = $callbackArguments[$param->name];
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                $arguments[$param->name] = null;
            }
        }

        return $arguments;
    }

    /**
     * Get the callback function's parameters.
     *
     * @param callable $callback
     *
     * @return array
     */
    private function getParameters(callable $callback)
    {
        if (is_array($callback)) {
            $mirror = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_object($callback) && !$callback instanceof \Closure) {
            $mirror = new \ReflectionObject($callback);
            $mirror = $mirror->getMethod('__invoke');
        } else {
            $mirror = new \ReflectionFunction($callback);
        }

        return $mirror->getParameters();
    }
}
