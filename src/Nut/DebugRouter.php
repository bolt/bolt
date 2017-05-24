<?php

namespace Bolt\Nut;

use ArrayObject;
use Silex\Route;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Nut command to dump system routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugRouter extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:router')
            ->setDescription('System route debug dumper.')
            ->addArgument('name', InputArgument::OPTIONAL, 'A route name')
            ->addOption('sort-route', null, InputOption::VALUE_NONE, 'Sort in order of route name (default).')
            ->addOption('sort-pattern', null, InputOption::VALUE_NONE, 'Sort in order of URI patterns.')
            ->addOption('sort-method', null, InputOption::VALUE_NONE, 'Sort in order of HTTP method grouping allowed.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app->flush();
        $name = $input->getArgument('name');
        if ($name === null) {
            return $this->executeAll($input, $output);
        }

        $table = new Table($output);
        $table->setHeaders([
            [
                'Property',
                'Value',
            ],
        ]);

        $routes = $this->app['routes'];
        $route = $routes->get($name);

        $table->addRows([
            ['Route Name', $name],
            ['Path', $route->getPath()],
            ['Host', $route->getHost() ?: 'ANY'],
            ['Scheme', implode('|', $route->getSchemes()) ?: 'ANY'],
            ['Method(s)', $this->getMethods($route)],
            ['Requirements', $this->formatArrayAsYaml($route->getRequirements()) ?: 'NO CUSTOM'],
            ['Defaults', $this->formatArrayAsYaml($route->getDefaults())],
            ['Options', $this->formatArrayAsYaml($route->getOptions())],
        ]);

        $table->render();

        return 0;
    }

    /**
     * @param mixed $array
     *
     * @return string
     */
    protected function formatArrayAsYaml($array)
    {
        if (!(is_array($array) || !$array instanceof ArrayObject)) {
            return $array;
        }
        array_walk_recursive($array, function (&$item) {
            $item = is_object($item) ? get_class($item) : $item;
        });

        $yaml = Yaml::dump($array, 4, 2);

        return trim($yaml);
    }

    protected function executeAll(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            [
                'Route Name',
                'Method(s)',
                'Scheme',
                'Host',
                'Path',
            ],
        ]);
        $routes = (array) $this->app['routes']->getIterator();

        if ($input->getOption('sort-route')) {
            $routes = $this->sortRoutes($routes);
        }
        if ($input->getOption('sort-pattern')) {
            $routes = $this->sortPattern($routes);
        }
        if ($input->getOption('sort-method')) {
            $routes = $this->sortMethods($routes);
        }

        foreach ($routes as $bindName => $route) {
            $table->addRow([
                $bindName,
                $this->getMethods($route),
                implode('|', $route->getSchemes()) ?: ' ANY',
                $route->getHost() ?: ' ANY',
                $route->getPath(),
            ]);
        }

        $table->render();
    }

    /**
     * Sort routes by their route binding name.
     *
     * @param Route[] $routes
     *
     * @return Route[]
     */
    private function sortRoutes(array $routes)
    {
        ksort($routes);

        return $routes;
    }

    /**
     * Sort routes by their URI pattern.
     *
     * @param Route[] $routes
     *
     * @return Route[]
     */
    private function sortPattern(array $routes)
    {
        uasort($routes, function (Route $a, Route $b) {
            if ($a->getPath() === $b->getPath()) {
                return 0;
            }
            $a = str_replace('{', '', str_replace('}', '', $a->getPath()));
            $b = str_replace('{', '', str_replace('}', '', $b->getPath()));

            return ($a < $b) ? -1 : 1;
        });

        return $routes;
    }

    /**
     * Get a route's HTTP methods as a sorted string.
     *
     * @param Route $route
     *
     * @return string
     */
    private function getMethods(Route $route)
    {
        $methods = $route->getMethods();
        sort($methods);

        return implode('|', $methods) ?: 'ANY';
    }

    /**
     * Sort routes by their allowed HTTP method(s).
     *
     * @param Route[] $routes
     *
     * @return Route[]
     */
    private function sortMethods(array $routes)
    {
        uasort($routes, function (Route $a, Route $b) {
            if ($a->getMethods() === $b->getMethods()) {
                return 0;
            }

            return ($a->getMethods() < $b->getMethods()) ? -1 : 1;
        });

        return $routes;
    }
}
