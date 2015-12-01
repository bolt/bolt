<?php

namespace Bolt\Extension;

use Bolt\Twig\DynamicExtension;
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

    /** @return \Silex\Application */
    abstract protected function getApp();

    /** @return string */
    abstract public function getName();

    /**
     * Call this in register method.
     *
     * @param Application $app
     */
    protected function registerTwigExtension(Application $app)
    {
        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function ($twig) {
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
                    if ($this->safeTwigExtension) {
                        $twig->addExtension($this->safeTwigExtension);
                    }

                    return $twig;
                }
            )
        );
    }

    /**
     * Add a Twig Function.
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
