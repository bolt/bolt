<?php
namespace Bolt\Controller;

/**
 * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement} instead.
 */
class Routing extends Requirement
{
    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::anyContentType} instead.
     */
    public function getAnyContentTypeRequirement()
    {
        return $this->anyContentType();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::pluralContentTypes} instead.
     */
    public function getPluralContentTypeRequirement()
    {
        return $this->pluralContentTypes();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::anyTaxonomyType} instead.
     */
    public function getAnyTaxonomyTypeRequirement()
    {
        return $this->anyTaxonomyType();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::pluralTaxonomyTypes} instead.
     */
    public function getPluralTaxonomyTypeRequirement()
    {
        return $this->pluralTaxonomyTypes();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::singleTaxonomy} instead.
     *
     * @param string $taxonomyName
     * @param string $emptyValue
     *
     * @return string
     */
    public function getTaxonomyRequirement($taxonomyName, $emptyValue = 'none')
    {
        return $this->singleTaxonomy($taxonomyName, $emptyValue);
    }
}
