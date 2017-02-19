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
        $this->deprecationNotice(__FUNCTION__, 'anyContentType');

        return $this->anyContentType();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::pluralContentTypes} instead.
     */
    public function getPluralContentTypeRequirement()
    {
        $this->deprecationNotice(__FUNCTION__, 'pluralContentTypes');

        return $this->pluralContentTypes();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::anyTaxonomyType} instead.
     */
    public function getAnyTaxonomyTypeRequirement()
    {
        $this->deprecationNotice(__FUNCTION__, 'anyTaxonomyType');

        return $this->anyTaxonomyType();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see Bolt\Controller\Requirement::pluralTaxonomyTypes} instead.
     */
    public function getPluralTaxonomyTypeRequirement()
    {
        $this->deprecationNotice(__FUNCTION__, 'pluralTaxonomyTypes');

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
        $this->deprecationNotice(__FUNCTION__, 'singleTaxonomy');

        return $this->singleTaxonomy($taxonomyName, $emptyValue);
    }

    /**
     * @param string $old Old function name
     * @param string $new New function name
     *
     * @internal
     */
    private function deprecationNotice($old, $new)
    {
        @trigger_error(
            sprintf(
                'The app/config/routing.yml routing requirement parameter "Bolt\Controllers\Routing::%s" is deprecated and will be removed in version 4.0. Use "controller.requirement:%s" instead',
                $old,
                $new
            ),
            E_USER_DEPRECATED
        );
    }
}
