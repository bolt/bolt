<?php
namespace Bolt\Controllers;

use Doctrine\Common\Collections\ArrayCollection;
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
     *
     * @param Silex\Application $app
     *
     * @return Silex\ControllerCollection
     */
    public function connect(Silex\Application $app)
    {
        if (self::$app === false) {
            self::$app = $app;
        }

        $routes = $app['config']->get('routing');
        $routes = is_array($routes) ? $routes : array();

        $ctr = $this->addRoutes($app, $routes);

        return $ctr;
    }

    /**
     * Add routes based on the parsed array
     *
     * @param Silex\Application $app
     * @param array             $routes
     *
     * @return Silex\ControllerCollection
     */
    private function addRoutes(Silex\Application $app, array $routes)
    {
        /** @var $ctr Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        foreach ($routes as $name => $config) {
            $config = new ArrayCollection($config);

            if (!$path = $config['path']) {
                continue;
            }
            if (!$defaults = $config['defaults']) {
                continue;
            }
            $defaults = new ArrayCollection($defaults);

            if (!$to = $defaults->remove('_controller')) {
                continue;
            }
            if (strpos($to, '::') > 0) {
                $to = explode('::', $to);
            }
            $route = $ctr->match($path, $to);

            $before = $defaults->remove('_before') ?: '::before';
            if (substr($before, 0, 2) === '::' && is_array($to)) {
                $before = array($to[0], substr($before, 2));
            }
            $route->before($before);

            $after = $defaults->remove('_after') ?: '::after';
            if (substr($after, 0, 2) === '::' && is_array($to)) {
                $after = array($to[0], substr($after, 2));
            }
            $route->after($after);

            foreach ($defaults as $key => $value) {
                $route->value($key, $value);
            }

            foreach ($config['requirements'] ?: array() as $variable => $regexp) {
                $properRegexp = $this->getProperRegexp($regexp);
                $route->assert($variable, $properRegexp);
            }

            if ($host = $config['host']) {
                $route->setHost($host);
            }

            $route->bind($name);
        }

        return $ctr;
    }

    /**
     * Return a regex from a function
     *
     * @param string|array $regexp
     * @return string
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
     *
     * @param string      $taxonomyName
     * @param string|null $emptyValue
     *
     * @return string
     */
    public static function getTaxonomyRequirement($taxonomyName, $emptyValue = null)
    {
        $taxonomyValues = self::$app['config']->get('taxonomy/' . $taxonomyName . '/options');

        // If by accident, someone uses a "tags" taxonomy.
        if (empty($taxonomyValues)) {
            return "[a-z0-9-_]+";
        }
        $taxonomyValues = array_keys($taxonomyValues);
        $requirements = implode('|', $taxonomyValues);

        if ($emptyValue !== null) {
            $requirements .= '|' . $emptyValue;
        }

        return $requirements;
    }
}
