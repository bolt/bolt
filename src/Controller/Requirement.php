<?php
namespace Bolt\Controller;

use Bolt\Config;

/**
 * Defines route requirements from content types / taxonomy configurations
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Requirement
{
    /** @var Config $config */
    protected $config;

    /**
     * Requirement constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Return plural and singular content type slugs.
     *
     * @return string
     */
    public function anyContentType()
    {
        return $this->configAssert('contenttypes', true);
    }

    /**
     * Return only plural content type slugs.
     *
     * @return string
     */
    public function pluralContentTypes()
    {
        return $this->configAssert('contenttypes', false);
    }

    /**
     * Return plural and singular taxonomy type slugs.
     *
     * @return string
     */
    public function anyTaxonomyType()
    {
        return $this->configAssert('taxonomy', true);
    }

    /**
     * Return only plural taxonomy type slugs.
     *
     * @return string
     */
    public function pluralTaxonomyTypes()
    {
        return $this->configAssert('taxonomy', false);
    }

    /**
     * Return slugs of existing taxonomy values.
     *
     * @param string $taxonomyName
     * @param string $emptyValue
     *
     * @return string
     */
    public function singleTaxonomy($taxonomyName, $emptyValue = 'none')
    {
        $taxonomyValues = $this->config->get('taxonomy/' . $taxonomyName . '/options');

        // If by accident, someone uses a "tags" taxonomy.
        if (empty($taxonomyValues)) {
            return '[a-z0-9-_]+';
        }
        $taxonomyValues = array_keys($taxonomyValues);
        $requirements = implode('|', $taxonomyValues);

        if ($emptyValue !== null) {
            $requirements .= '|' . $emptyValue;
        }

        return $requirements;
    }

    /**
     * Gets slugs from config imploded for regex.
     *
     * @param string $key      contenttypes or taxonomy
     * @param bool   $singular Include singular slugs
     *
     * @return string
     */
    protected function configAssert($key, $singular)
    {
        $types = $this->config->get($key);
        // No types, nothing to assert. The route _DOES_ expect a string, so
        // we return a regex that never matches.
        if (empty($types)) {
            return '$.';
        }

        $slugs = [];
        foreach ($types as $type) {
            $slugs[] = $type['slug'];
            if ($singular) {
                $slugs[] = $type['singular_slug'];
            }
        }

        return implode('|', $slugs);
    }
}
