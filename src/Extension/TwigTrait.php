<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Twig\DynamicExtension;
use Pimple as Container;
use Twig_Error_Loader as LoaderError;
use Twig_Loader_Filesystem as FilesystemLoader;
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
    /** @var bool */
    private $pathAdded = false;

    /**
     * Returns a list of twig functions to register.
     *
     * Example:
     * <pre>
     *  return [
     *      'foo' => 'fooFunction',
     *      'bar' => ['barFunction', ['is_safe' => ['html']]]
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
     *      'bar' => ['barFilter', ['is_safe' => ['html']]]]
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
     * Returns a list of relative paths to add to Twig's path array.
     *
     * Example:
     * <pre>
     *  return [
     *      'templates/foo',
     *      'templates/bar' => ['position' => 'prepend', 'namespace' => 'MyExtension']
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerTwigPaths()
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

        foreach ($this->registerTwigPaths() as $key => $value) {
            if (is_array($value)) {
                $this->addTwigPath($key, $value);
            } else {
                $this->addTwigPath($value);
            }
        }

        $this->loadedTwig = true;
    }

    /**
     * Append a path to Twig's path array.
     *
     * @param string $path
     * @param array  $options
     *
     * @throws \Twig_Error_Loader
     */
    private function addTwigPath($path, array $options = [])
    {
        if ($path === 'templates' && $this->pathAdded) {
            return;
        }

        $app = $this->getContainer();

        $position = isset($options['position']) ? $options['position'] : 'append';
        $namespace = isset($options['namespace']) ? $options['namespace'] : FilesystemLoader::MAIN_NAMESPACE;

        $dir = $this->getBaseDirectory()->getDir($path);
        if (!$dir->exists()) {
            return;
        }
        try {
            if ($position === 'prepend') {
                $app['twig.loader.bolt_filesystem']->prependDir($dir, $namespace);
            } else {
                $app['twig.loader.bolt_filesystem']->addDir($dir, $namespace);
            }
        } catch (LoaderError $e) {
            $app['logger.system']->critical(sprintf('%s was unable to add the Twig path %s. %s', $this->getName(), $path, $e->getMessage()), ['event' => 'exception', 'exception' => $e]);
        }
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

    /**
     * Render a Twig template.
     *
     * @param string $template
     * @param array  $context
     *
     * @return string
     */
    protected function renderTemplate($template, array $context = [])
    {
        $app = $this->getContainer();

        if ($this->pathAdded === false) {
            $this->addTwigPath('templates');
            $this->pathAdded = true;
        }

        return $app['twig']->render($template, $context);
    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return string */
    abstract public function getName();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();
}
