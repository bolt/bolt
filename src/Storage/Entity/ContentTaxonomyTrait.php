<?php
namespace Bolt\Storage\Entity;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Trait class for ContentType relations.
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
trait ContentTaxonomyTrait
{
    /**
     * NOTE: This probably needs to implement, or be called by, Bolt\Storage::getTaxonomy()
     *
     * @param string $taxonomyType
     */
    public function getTaxonomy($taxonomyType)
    {
    }

    /**
     * Set the 'group', 'groupname' and 'sortorder' properties of the current object.
     *
     * @param string  $group
     * @param string  $name
     * @param string  $taxonomytype
     * @param integer $sortorder
     *
     * @return void
     */
    public function setGroup($group, $name, $taxonomytype, $sortorder = 0)
    {
        $this->group = [
            'slug' => $group,
            'name' => $name,
        ];

        $hasSortOrder = $this->app['config']->get('taxonomy/' . $taxonomytype . '/has_sortorder');

        // Only set the sortorder, if the contenttype has a taxonomy that has sortorder
        if ($hasSortOrder !== false) {
            $this->group['order'] = (int) $sortorder;
        }

        // Set the 'index', so we can sort on it later.
        $index = array_search($group, array_keys($this->app['config']->get('taxonomy/' . $taxonomytype . '/options')));

        if ($index !== false) {
            $this->group['index'] = $index;
        } else {
            $this->group['index'] = 2147483647; // Max for 32 bit int.
        }
    }

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
        if (!isset($this->contenttype['taxonomy']) || !in_array($taxonomyType, $this->contenttype['taxonomy']) || empty($slug)) {
            return false;
        }

        // Make sure sortorder is set correctly;
        if ($this->app['config']->get('taxonomy/' . $taxonomyType . '/has_sortorder') === false) {
            $sortorder = 0;
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

    /**
     * Sort the taxonomy of the current object, based on the order given in taxonomy.yml.
     *
     * @return void
     */
    public function sortTaxonomy()
    {
        if (empty($this->taxonomy)) {
            // Nothing to do here.
            return;
        }

        foreach (array_keys($this->taxonomy) as $type) {
            $taxonomytype = $this->app['config']->get('taxonomy/' . $type);
            // Don't order tags.
            if ($taxonomytype['behaves_like'] == 'tags') {
                continue;
            }

            // Order them by the order in the contenttype.
            $new = [];
            foreach ($this->app['config']->get('taxonomy/' . $type . '/options') as $key => $value) {
                if ($foundkey = array_search($key, $this->taxonomy[$type])) {
                    $new[$foundkey] = $value;
                } elseif ($foundkey = array_search($value, $this->taxonomy[$type])) {
                    $new[$foundkey] = $value;
                }
            }
            $this->taxonomy[$type] = $new;
        }
    }
}
