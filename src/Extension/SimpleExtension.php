<?php

namespace Bolt\Extension;

use Bolt\Twig\TwigExtensionTrait;
use Silex\Application;

/**
 * This will replace current BaseExtension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension implements \Twig_ExtensionInterface, \Twig_Extension_GlobalsInterface
{
    use TwigExtensionTrait;

    private $twigFunctions = [];
    private $twigFilters = [];

    abstract public function initialize(Application $app);

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
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
     * @param string $name
     * @param string $callback
     * @param array  $options
     */
    public function addTwigFunction($name, $callback, $options = [])
    {
        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $this->twigFunctions[] = new \Twig_SimpleFunction($name, $callback, $options);
    }

    /**
     * Add a Twig Filter.
     *
     * @param string $name
     * @param string $callback
     * @param array  $options
     */
    public function addTwigFilter($name, $callback, $options = [])
    {
        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $this->twigFilters[] = new \Twig_SimpleFilter($name, $callback, $options);
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
