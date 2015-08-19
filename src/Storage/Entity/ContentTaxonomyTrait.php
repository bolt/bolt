<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType taxonomy.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentTaxonomyTrait
{
    /**
     * Set a taxonomy for the current object.
     *
     * @param string       $taxonomyType
     * @param string|array $slug
     * @param string       $name
     * @param integer      $sortorder
     *
     * @return boolean
     */
    public function setTaxonomy($taxonomyType, $slug, $name = '', $sortorder = 0)
    {
        // If $value is an array, recurse over it, adding each one by itself.
        if (is_array($slug)) {
            foreach ($slug as $single) {
                $this->setTaxonomy($taxonomyType, $single, '', $sortorder);
            }

            return true;
        }

        // Only add a taxonomy, if the taxonomytype is actually set in the contenttype
        if (!isset($this->contenttype['taxonomy']) || !in_array($taxonomyType, $this->contenttype['taxonomy'])) {
            return false;
        }

        // Make sure sortorder is set correctly;
        if ($this->app['config']->get('taxonomy/' . $taxonomyType . '/has_sortorder') === false) {
            $sortorder = false;
        } else {
            $sortorder = (int) $sortorder;
            // Note: by doing this we assume a contenttype can have only one taxonomy which has has_sortorder: true.
            $this->sortorder = $sortorder;
        }

        // Make the 'key' of the array an absolute link to the taxonomy.
        try {
            $link = $this->app['url_generator']->generate(
                'taxonomylink',
                [
                    'taxonomytype' => $taxonomyType,
                    'slug'         => $slug,
                ]
                );
        } catch (RouteNotFoundException $e) {
            // Fallback to unique key (yes, also a broken link)
            $link = $taxonomyType . '/' . $slug;
        }

        // Set the 'name', for displaying the pretty name, if there is any.
        if ($this->app['config']->get('taxonomy/' . $taxonomyType . '/options/' . $slug)) {
            $name = $this->app['config']->get('taxonomy/' . $taxonomyType . '/options/' . $slug);
        } elseif (empty($name)) {
            $name = $slug;
        }

        $this->taxonomy[$taxonomyType][$link] = $name;

        // If it's a "grouping" type, set $this->group.
        if ($this->app['config']->get('taxonomy/' . $taxonomyType . '/behaves_like') == 'grouping') {
            $this->setGroup($slug, $name, $taxonomyType, $sortorder);
        }

        return true;
    }
}
