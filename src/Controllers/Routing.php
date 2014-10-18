<?php
namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;

/**
 * Configurable routes controller
 *
 * Add routes from a configuration file.
 */
class Routing implements ControllerProviderInterface
{
    // Dirty trick to allow for easy route-requirements
    // @todo fix this (create service, abstract away, figure something else..)
    private static $app = false;

    /**
     * Connect this controller to the application
     */
    public function connect(Silex\Application $app)
    {
        if (self::$app === false) {
            self::$app = $app;
        }

        $ctr = false;

        $routes = $app['config']->get('routing');
        if (is_array($routes)) {
            $ctr = $this->addRoutes($app, $routes);
        }

        if ($ctr === false) {
            $ctr = $app['controllers_factory'];
        }

        return $ctr;
    }

    /**
     * Add routes based on the parsed array
     */
    private function addRoutes(Silex\Application $app, array $routes)
    {
        /** @var $ctr Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        foreach ($routes as $binding => $routeconfig) {
            $path = false;
            $to = false;
            $route = false;
            $host = false;
            $_before = false;
            $_after = false;
            $defaults = array();
            $requirements = array();

            // set some defaults in the YAML
            if ((!isset($routeconfig['defaults'])) || (!isset($routeconfig['defaults']['_before']))) {
                $routeconfig['defaults']['_before'] = '::before';
            }
            if ((!isset($routeconfig['defaults'])) || (!isset($routeconfig['defaults']['_after']))) {
                $routeconfig['defaults']['_after'] = '::after';
            }

            // parse YAML structure

            if (isset($routeconfig['path'])) {
                $path = $routeconfig['path'];
            }
            if (isset($routeconfig['defaults'])) {
                $defaults = $routeconfig['defaults'];
                if (isset($defaults['_controller'])) {
                    $to = $defaults['_controller'];
                    if (strpos($to, '::') > 0) {
                        $to = explode('::', $defaults['_controller']);
                    }
                    unset($defaults['_controller']);
                }
                if (isset($defaults['_before'])) {
                    if ((substr($defaults['_before'], 0, 2) == '::') && (is_array($to))) {
                        $_before = array($to[0], substr($defaults['_before'], 2));
                    } else {
                        $_before = $defaults['_before'];
                    }
                    unset($defaults['_before']);
                }
                if (isset($defaults['_after'])) {
                    if ((substr($defaults['_after'], 0, 2) == '::') && (is_array($to))) {
                        $_after = array($to[0], substr($defaults['_after'], 2));
                    } else {
                        $_after = $defaults['_after'];
                    }
                    unset($defaults['_after']);
                }
            }
            if (isset($routeconfig['requirements']) && (is_array($routeconfig['requirements']))) {
                $requirements = $routeconfig['requirements'];
            }
            if (isset($routeconfig['host'])) {
                $host = $routeconfig['host'];
            }

            // build an actual route

            if (($path !== false) && ($to !== false)) {
                $route = $ctr->match($path, $to);
            }
            if ($route !== false) {
                if (($_before !== false) && (is_callable($_before))) {
                    $route->before($_before);
                }
                if (($_after !== false) && (is_callable($_after))) {
                    $route->after($_after);
                }

                foreach ($requirements as $variable => $regexp) {
                    $proper_regexp = $this->getProperRegexp($regexp);
                    $route->assert($variable, $proper_regexp);
                }
                foreach ($defaults as $variable => $default) {
                    $route->value($variable, $default);
                }
                if ($host !== false) {
                    $route->setHost($host);
                }

                $route->bind($binding);
            }
        }

        return $ctr;
    }

    /**
     * Return a proper regexp
     *
     * Bolt allows
     */
    private function getProperRegexp($regexp)
    {
        if (is_array($regexp)) {
            return call_user_func_array($regexp[0], $regexp[1]);
        }

        if (strpos($regexp, '::') > 0) {
            return call_user_func($regexp);
        }

        return $regexp;
    }

    /**
     * Return plural and singular contenttypeslugs
     */
    public static function getAnyContentTypeRequirement()
    {
        return self::$app['storage']->getContentTypeAssert(true);
    }

    /**
     * Return only plural contenttypeslugs
     */
    public static function getPluralContentTypeRequirement()
    {
        return self::$app['storage']->getContentTypeAssert(false);
    }

    /**
     * Return plural and singular taxonomytypeslugs
     */
    public static function getAnyTaxonomyTypeRequirement()
    {
        return self::$app['storage']->getTaxonomyTypeAssert(true);
    }

    /**
     * Return only plural taxonomytypeslugs
     */
    public static function getPluralTaxonomyTypeRequirement()
    {
        return self::$app['storage']->getTaxonomyTypeAssert(false);
    }

    /**
     * Return slugs of existing taxonomy values.
     */
    public static function getTaxonomyRequirement($taxonomyName, $emptyValue = null)
    {
        $taxonomyValues = self::$app['config']->get('taxonomy/' . $taxonomyName . '/options');

        // If by accident, someone uses a "tags" taxonomy.
        if ($taxonomyValues == null) {
            return "[a-z0-9-_]+";
        }
        $taxonomyValues = array_keys($taxonomyValues);
        $requirements = implode('|', $taxonomyValues);

        if ($emptyValue != null) {
            $requirements .= '|' . $emptyValue;
        }

        return $requirements;
    }
}
