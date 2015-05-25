<?php
namespace Bolt\Controllers;

use Bolt\Controller\Requirement;

/**
 * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement} instead.
 */
class Routing extends Requirement
{
    /**
     * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement::anyContentType} instead.
     */
    public function getAnyContentTypeRequirement()
    {
        return $this->anyContentType();
    }

    /**
     * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement::pluralContentTypes} instead.
     */
    public function getPluralContentTypeRequirement()
    {
        return $this->pluralContentTypes();
    }

    /**
     * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement::anyTaxonomyType} instead.
     */
    public function getAnyTaxonomyTypeRequirement()
    {
        return $this->anyTaxonomyType();
    }

    /**
     * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement::pluralTaxonomyTypes} instead.
     */
    public function getPluralTaxonomyTypeRequirement()
    {
        return $this->pluralTaxonomyTypes();
    }

    /**
     * @deprecated since version 2.3, use {@see Bolt\Controller\Requirement::singleTaxonomy} instead.
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
