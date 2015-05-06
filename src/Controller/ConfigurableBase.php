<?php
namespace Bolt\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\CallbackResolver;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add routes from the configuration file.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class ConfigurableBase extends Base
{
    /**
     * @var CallbackResolver
     */
    private $callbackResolver;

    public function connect(Application $app)
    {
        $this->callbackResolver = $app['callback_resolver'];
        return parent::connect($app);
    }

    public function before(Request $request)
    {
    }

    public function after(Request $request, Response $response)
    {
    }

    /**
     * Return routes (as arrays) that will be converted to route objects.
     *
     * @return array
     */
    abstract protected function getConfigurationRoutes();

    protected function addRoutes(ControllerCollection $c)
    {
        $routes = $this->getConfigurationRoutes();
        if (!is_array($routes)) {
            throw new \InvalidArgumentException('getConfigurationRoutes return an array');
        }

        foreach ($routes as $name => $config) {
            $this->addRoute($c, $name, $config);
        }
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

        $cls = null;
        if (strpos($to, '::') > 0) {
            $parts = explode('::', $to);
            $cls = reset($parts);
        }

        $route = $ctr->match($path, $to);

        $before = $defaults->remove('_before') ?: '::before';
        if (substr($before, 0, 2) === '::' && $cls) {
            $before = array($cls, substr($before, 2));
        }
        $route->before($before);

        $after = $defaults->remove('_after') ?: '::after';
        if (substr($after, 0, 2) === '::' && $cls) {
            $after = array($cls, substr($after, 2));
        }
        $route->after($after);

        foreach ($defaults as $key => $value) {
            $route->value($key, $value);
        }

        foreach ($config['requirements'] ?: array() as $variable => $callback) {
            $callback = $this->callbackResolver->resolveCallback($callback);
            $route->assert($variable, call_user_func($callback));
        }

        if ($host = $config['host']) {
            $route->setHost($host);
        }

        if ($methods = $config['methods']) {
            $route->setMethods($methods);
        }

        $route->bind($name);
    }

    /**
     * Return plural and singular contenttypeslugs.
     */
    public function getAnyContentTypeRequirement()
    {
        return $this->getContentTypeAssert(true);
    }

    /**
     * Return only plural contenttypeslugs.
     */
    public function getPluralContentTypeRequirement()
    {
        return $this->getContentTypeAssert();
    }

    /**
     * Get a value to use in 'assert() with the available contenttypes.
     *
     * @param bool $includesingular
     *
     * @return string $contenttypes
     */
    protected function getContentTypeAssert($includesingular = false)
    {
        $slugs = array();
        foreach ($this->app['config']->get('contenttypes') as $type) {
            $slugs[] = $type['slug'];
            if ($includesingular) {
                $slugs[] = $type['singular_slug'];
            }
        }

        return implode("|", $slugs);
    }

    /**
     * Return plural and singular taxonomytypeslugs.
     */
    public function getAnyTaxonomyTypeRequirement()
    {
        return $this->getTaxonomyTypeAssert(true);
    }

    /**
     * Return only plural taxonomytypeslugs.
     */
    public function getPluralTaxonomyTypeRequirement()
    {
        return $this->getTaxonomyTypeAssert();
    }

    /**
     * Get a value to use in 'assert() with the available taxonomytypes.
     *
     * @param bool $includesingular
     *
     * @return string $taxonomytypes
     */
    protected function getTaxonomyTypeAssert($includesingular = false)
    {
        $taxonomytypes = $this->app['config']->get('taxonomy');

        // No taxonomies, nothing to assert. The route _DOES_ expect a string, so
        // we return a regex that never matches.
        if (empty($taxonomytypes)) {
            return "$.";
        }

        $slugs = array();
        foreach ($taxonomytypes as $type) {
            $slugs[] = $type['slug'];
            if ($includesingular) {
                $slugs[] = $type['singular_slug'];
            }
        }

        return implode("|", $slugs);
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
