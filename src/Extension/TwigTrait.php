<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Twig\SecurityPolicy;
use Pimple as Container;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig function/filter addition and interface functions for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TwigTrait
{
    /** @var TwigFilter[] */
    private $twigFilters = [];
    /** @var TwigFunction[] */
    private $twigFunctions = [];
    /** @var string[] */
    private $safeFilterNames = [];
    /** @var string[] */
    private $safeFunctionNames = [];
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
                    $this->loadTwig($twig);

                    return $twig;
                }
            )
        );
    }

    /**
     * Adds filters and functions to Twig Environment.
     *
     * @param Environment $twig
     */
    private function loadTwig(Environment $twig)
    {
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

        foreach ($this->twigFunctions as $twigFunction) {
            $twig->addFunction($twigFunction);
        }

        foreach ($this->twigFilters as $twigFilter) {
            $twig->addFilter($twigFilter);
        }

        $this->updateSandboxPolicy($twig);
    }

    private function updateSandboxPolicy(Environment $twig)
    {
        if (!$twig->hasExtension(SandboxExtension::class)) {
            return;
        }

        /** @var SandboxExtension $sandbox */
        $sandbox = $twig->getExtension(SandboxExtension::class);
        $policy = $sandbox->getSecurityPolicy();
        if (!$policy instanceof SecurityPolicy) {
            return;
        }

        foreach ($this->safeFunctionNames as $name) {
            $policy->addAllowedFunction($name);
        }

        foreach ($this->safeFilterNames as $name) {
            $policy->addAllowedFilter($name);
        }
    }

    /**
     * Append a path to Twig's path array.
     *
     * @param string $path
     * @param array  $options
     *
     * @throws LoaderError
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

        $this->twigFunctions[] = new TwigFunction($name, $callback, $options);

        if ($safe) {
            $this->safeFunctionNames[] = $name;
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

        $this->twigFilters[] = new TwigFilter($name, $callback, $options);

        if ($safe) {
            $this->safeFilterNames[] = $name;
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
