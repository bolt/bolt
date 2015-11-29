<?php

namespace Bolt\Extension;

use Bolt\Twig\TwigExtensionTrait;
use LogicException;
use Silex\Application;
use Twig_ExtensionInterface;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Twig function/filter addition and interface functions for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TwigTrait
{
    use TwigExtensionTrait;

    /** @var \Twig_SimpleFunction[] */
    private $twigFunctions = [];
    /** @var \Twig_SimpleFilter[] */
    private $twigFilters = [];

    /** @return \Silex\Application */
    abstract protected function getApp();

    /**
     * {@inheritdoc}
     */
    protected function registerTwigExtension(Application $app)
    {
        if (!$this instanceof Twig_ExtensionInterface) {
            throw new LogicException('Extension must implement Twig_ExtensionInterface to register Twig extensions');
        }

        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function ($twig) {
                    $twig->addExtension($this);

                    return $twig;
                }
            )
        );

        $this->initialize($app);
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
        if (!$this instanceof Twig_ExtensionInterface) {
            throw new LogicException('Extension must implement Twig_ExtensionInterface to add Twig functions');
        }

        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $this->twigFunctions[] = new Twig_SimpleFunction($name, $callback, $options);
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
        if (!$this instanceof Twig_ExtensionInterface) {
            throw new LogicException('Extension must implement Twig_ExtensionInterface to add Twig filters');
        }

        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $this->twigFilters[] = new Twig_SimpleFilter($name, $callback, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->twigFunctions;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->twigFilters;
    }
}
