<?php
namespace Bolt\Controllers;

use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * Configurable routes controller
 *
 * Add routes from a configuration file.
 */
class Routing implements ControllerProviderInterface
{
    /** @var Application */
    protected $app;

    /**
     * Connect this controller to the application
     *
     * @param Application $app
     *
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->app = $app;

        $routes = $app['config']->get('routing');
        $routes = is_array($routes) ? $routes : array();

        $ctr = $this->addRoutes($routes);

        return $ctr;
    }

    /**
     * Add routes based on the parsed array
     *
     * @param array $routes
     *
     * @return ControllerCollection
     */
    protected function addRoutes(array $routes)
    {
        /** @var $ctr ControllerCollection */
        $ctr = $this->app['controllers_factory'];

        foreach ($routes as $name => $config) {
            $this->addRoute($ctr, $name, $config);
        }

        return $ctr;
    }

    protected function addRoute(ControllerCollection $ctr, $name, array $config)
    {
        $config = new ArrayCollection($config);

        if (!$path = $config['path']) {
            return;
        }
        if (!$defaults = $config['defaults']) {
            return;
        }
        $defaults = new ArrayCollection($defaults);

        if (!$to = $defaults->remove('_controller')) {
            return;
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

    /**
     * Return a regex from a function
     *
     * @param string|array $regexp
     * @return string
     */
    protected function getProperRegexp($regexp)
    {
        if (is_array($regexp)) {
            list($method, $args) = $regexp;
        } elseif (strpos($regexp, '::') <= 0) {
            return $regexp;
        } else {
            $method = $regexp;
            $args = array();
        }

        $method = explode('::', $method);
        if ($method[0] === __CLASS__) {
            $method[0] = $this;
        }

        return call_user_func_array($method, $args);
    }

    /**
     * Return plural and singular contenttypeslugs
     */
    public function getAnyContentTypeRequirement()
    {
        return $this->app['storage']->getContentTypeAssert(true);
    }

    /**
     * Return only plural contenttypeslugs
     */
    public function getPluralContentTypeRequirement()
    {
        return $this->app['storage']->getContentTypeAssert(false);
    }

    /**
     * Return plural and singular taxonomytypeslugs
     */
    public function getAnyTaxonomyTypeRequirement()
    {
        return $this->app['storage']->getTaxonomyTypeAssert(true);
    }

    /**
     * Return only plural taxonomytypeslugs
     */
    public function getPluralTaxonomyTypeRequirement()
    {
        return $this->app['storage']->getTaxonomyTypeAssert(false);
    }

    /**
     * Return slugs of existing taxonomy values.
     *
     * @param string      $taxonomyName
     * @param string|null $emptyValue
     *
     * @return string
     */
    public function getTaxonomyRequirement($taxonomyName, $emptyValue = null)
    {
        $taxonomyValues = $this->app['config']->get('taxonomy/' . $taxonomyName . '/options');

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
