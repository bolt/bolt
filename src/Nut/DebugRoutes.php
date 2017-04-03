<?php

namespace Bolt\Nut;

use Silex\Route;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to dump system routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugRoutes extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:routes')
            ->setDescription('System route debug dumper.')
            ->addOption('sort-bind', null, InputOption::VALUE_NONE, 'Sort in order of bind name (default).')
            ->addOption('sort-pattern', null, InputOption::VALUE_NONE, 'Sort in order of URI patterns.')
            ->addOption('sort-methods', null, InputOption::VALUE_NONE, 'Sort in order of HTTP method grouping allowed.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Bind name',
            'Path matching parameter',
            'Method(s)',
        ]);
        $routes = (array) $this->app['routes']->getIterator();

        if ($input->getOption('sort-bind')) {
            $routes = $this->sortBind($routes);
        }
        if ($input->getOption('sort-pattern')) {
            $routes = $this->sortPattern($routes);
        }
        if ($input->getOption('sort-methods')) {
            $routes = $this->sortMethods($routes);
        }

        foreach ($routes as $bindName => $route) {
            $table->addRow([
                $bindName,
                $route->getPath(),
                $this->getMethods($route),
            ]);
        }

        $table->render();
    }

    /**
     * Sort routes by their binding name.
     *
     * @param Route[] $routes
     *
     * @return Route[]
     */
    private function sortBind(array $routes)
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

        return implode('|', $methods) ?: 'ALL';
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
