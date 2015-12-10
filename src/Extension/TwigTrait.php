<?php

namespace Bolt\Extension;

use Bolt\Twig\DynamicExtension;
use Pimple as Container;
use Silex\Application;
use Twig_SimpleFilter as SimpleFilter;
use Twig_SimpleFunction as SimpleFunction;

/**
 * Twig function/filter addition and interface functions for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TwigTrait
{
    /** @var DynamicExtension */
    private $twigExtension;
    /** @var DynamicExtension */
    private $safeTwigExtension;
    /** @var bool */
    private $loadedTwig = false;

    /** @return Container */
    abstract protected function getContainer();

    /** @return string */
    abstract public function getName();

    /**
     * Returns a list of twig functions to register.
     *
     * Example:
     * <pre>
     *  return [
     *      'foo' => 'fooFunction',
     *      'bar' => ['barFunction', ['is_safe' => 'html']]
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerTwigFunctions()
    {
        return [];
    }

    /**
     * Returns a list of twig filters to register.
     *
     * Example:
     * <pre>
     *  return [
     *      'foo' => 'fooFilter',
     *      'bar' => ['barFilter', ['is_safe' => 'html']]
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerTwigFilters()
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendTwigService()
    {
        $app = $this->getContainer();

        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function ($twig) {
                    $this->loadTwig();

                    if ($this->twigExtension) {
                        $twig->addExtension($this->twigExtension);
                    }

                    return $twig;
                }
            )
        );

        $app['safe_twig'] = $app->share(
            $app->extend(
                'safe_twig',
                function ($twig) {
                    $this->loadTwig();

                    if ($this->safeTwigExtension) {
                        $twig->addExtension($this->safeTwigExtension);
                    }

                    return $twig;
                }
            )
        );
    }

    /**
     * Lazily adds filters and functions to our DynamicExtensions
     */
    private function loadTwig()
    {
        if ($this->loadedTwig) {
            return;
        }

        foreach ($this->registerTwigFunctions() as $name => $options) {
            if (is_string($options)) {
                $this->addTwigFunction($name, $options);
            } else {
                $this->addTwigFunction($name, $options[0], isset($options[1]) ? $options[1] : []);
            }
        }

        foreach ($this->registerTwigFilters() as $name => $options) {
            if (is_string($options)) {
                $this->addTwigFilter($name, $options);
            } else {
                $this->addTwigFilter($name, $options[0], isset($options[1]) ? $options[1] : []);
            }
        }

        $this->loadedTwig = true;
    }

    /**
     * Add a Twig Function.
     *
     * @internal Will be made private in 4.0. Use registerTwigFunctions() instead.
     *
     * @param string          $name
     * @param string|callable $callback
     * @param array           $options
     */
    protected function addTwigFunction($name, $callback, $options = [])
    {
        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $safe = method_exists($this, 'isSafe') && $this->isSafe();
        if (isset($options['safe'])) {
            $safe = (bool) $options['safe'];
            unset($options['safe']);
        }

        $function = new SimpleFunction($name, $callback, $options);

        if ($this->twigExtension === null) {
            $this->twigExtension = new DynamicExtension($this->getName());
        }
        $this->twigExtension->addFunction($function);

        if ($safe) {
            if ($this->safeTwigExtension === null) {
                $this->safeTwigExtension = new DynamicExtension($this->getName());
            }
            $this->safeTwigExtension->addFunction($function);
        }
    }

    /**
     * Add a Twig Filter.
     *
     * @internal Will be made private in 4.0. Use registerTwigFilters() instead.
     *
     * @param string          $name
     * @param string|callable $callback
     * @param array           $options
     */
    protected function addTwigFilter($name, $callback, $options = [])
    {
        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $safe = method_exists($this, 'isSafe') && $this->isSafe();
        if (isset($options['safe'])) {
            $safe = (bool) $options['safe'];
            unset($options['safe']);
        }

        $filter = new SimpleFilter($name, $callback, $options);

        if ($this->twigExtension === null) {
            $this->twigExtension = new DynamicExtension($this->getName());
        }
        $this->twigExtension->addFilter($filter);

        if ($safe) {
            if ($this->safeTwigExtension === null) {
                $this->safeTwigExtension = new DynamicExtension($this->getName());
            }
            $this->safeTwigExtension->addFilter($filter);
        }
    }
}
