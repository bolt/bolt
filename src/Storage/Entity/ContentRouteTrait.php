<?php

namespace Bolt\Storage\Entity;

use Bolt\Legacy\AppSingleton;
use Silex\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Trait class for ContentType routing.
 *
 * This is a breakout of the old Bolt\Content class and serves two main purposes:
 *   * Maintain backward compatibility for Bolt\Content through the remainder of
 *     the 2.x development/release life-cycle
 *   * Attempt to break up former functionality into sections of code that more
 *     resembles Single Responsibility Principles
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentRouteTrait
{
    /**
     * Creates a link to EDIT this record, if the user is logged in.
     *
     * @return string
     */
    public function editlink()
    {
        return $this->app['twig.runtime.bolt_routing']->editlink($this);
    }

    /**
     * Creates a URL for the content record.
     *
     * @param int $referenceType
     *
     * @return string|null
     */
    public function link($referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        list($name, $params) = $this->getRouteNameAndParams();
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->app['url_generator'];

        return $name ? $urlGenerator->generate($name, $params, $referenceType) : null;
    }

    /**
     * Checks if the current record is set as the homepage.
     *
     * @return bool
     */
    public function isHome()
    {
        $config = $this->app['config'];
        $homepage = $config->get('theme/homepage') ?: $config->get('general/homepage');
        $uriID = $this->contenttype['singular_slug'] . '/' . $this->get('id');
        $uriSlug = $this->contenttype['singular_slug'] . '/' . $this->get('slug');

        return $uriID === $homepage || $uriSlug === $homepage;
    }

    /**
     * Returns [route name, route params] for url generation, or null for various reasons.
     *
     * @return array|null
     */
    public function getRouteNameAndParams()
    {
        if (empty($this->id)) {
            return null;
        }

        // No links for records that are 'viewless'
        if (isset($this->contenttype['viewless']) && $this->contenttype['viewless'] === true) {
            return null;
        }

        list($name, $config) = $this->getRouteConfig();

        if (!$config) {
            return null;
        }

        $slug = $this->getLinkSlug();
        $availableParams = array_filter(
            array_merge(
                $config['defaults'] ?: [],
                $this->getRouteRequirementParams($config),
                [
                    'contenttypeslug' => $this->contenttype['singular_slug'],
                    'id'              => $this->id,
                    'slug'            => $slug,
                ]
            )
        );

        /** @var Route|null $route */
        $route = $this->app['routes']->get($name);
        if (!$route) {
            return null;
        }

        // Needed params as array keys
        $pathVars = $route->compile()->getPathVariables();
        $neededKeys = array_flip($pathVars);

        // Set the values of neededKeys from the availableParams.
        // This removes extra parameters that are not needed for url generation.
        $params = array_replace($neededKeys, array_intersect_key($availableParams, $neededKeys));

        return [$name, $params];
    }

    /**
     * Retrieves the first route applicable to the content as a two-element array consisting of the binding and the
     * route array. Returns `null` if there is no applicable route.
     *
     * @return array|null
     */
    protected function getRouteConfig()
    {
        $allroutes = $this->app['config']->get('routing');

        // First, try to find a custom route that's applicable
        foreach ($allroutes as $binding => $config) {
            if ($this->isApplicableRoute($config)) {
                return [$binding, $config];
            }
        }

        // Just return the 'generic' contentlink route.
        if (!empty($allroutes['contentlink'])) {
            return ['contentlink', $allroutes['contentlink']];
        }

        return null;
    }

    /**
     * Build a ContentType's route parameters.
     *
     * @param array $route
     *
     * @return array
     */
    protected function getRouteRequirementParams(array $route)
    {
        $params = [];
        if (isset($route['requirements'])) {
            foreach ($route['requirements'] as $fieldName => $requirement) {
                if ('\d{4}-\d{2}-\d{2}' === $requirement) {
                    // Special case, if we need to have a date
                    $params[$fieldName] = substr($this->get($fieldName), 0, 10);
                } elseif ($this->getTaxonomy() !== null && !$this->getTaxonomy()->getField($fieldName)->isEmpty()) {
                    // This is for new storage handling of taxonomies in
                    // contentroutes. If in legacy it will fall back to the one
                    // below.
                    $params[$fieldName] = $this->getTaxonomy()->getField($fieldName)->first()->getSlug();
                } elseif (isset($this->taxonomy[$fieldName])) {
                    // Turn something like '/groups/meta' to 'meta'. This is
                    // only for legacy storage.
                    $tempKeys = array_keys($this->taxonomy[$fieldName]);
                    $tempValues = explode('/', array_shift($tempKeys));
                    $params[$fieldName] = array_pop($tempValues);
                } elseif ($this->get($fieldName)) {
                    $params[$fieldName] = $this->get($fieldName);
                } elseif (isset($route['defaults'][$fieldName])) {
                    $params[$fieldName] = $route['defaults'][$fieldName];
                } else {
                    // unknown
                    $params[$fieldName] = null;
                }
            }
        }

        return $params;
    }

    /**
     * Check if a route is applicable to this record.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function isApplicableRoute(array $route)
    {
        return (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['singular_slug'])
            || (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['slug'])
            || (isset($route['recordslug']) && $route['recordslug'] === $this->getReference());
    }

    /**
     * Get the reference to this record, to uniquely identify this specific record.
     *
     * @return string
     */
    protected function getReference()
    {
        $reference = $this->contenttype['singular_slug'] . '/' . $this->getLinkSlug();

        return $reference;
    }

    /**
     * Get a record's slug depending on the type of object used.
     *
     * @return string|int
     */
    private function getLinkSlug()
    {
        if ($this instanceof Content) {
            return $this->slug ?: $this->id;
        }

        if (isset($this->values['slug'])) {
            return $this->values['slug'];
        }

        return $this->id;
    }
}
