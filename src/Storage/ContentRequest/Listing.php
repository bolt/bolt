<?php

namespace Bolt\Storage\ContentRequest;

/**
 * Helper class for ContentType overview listings.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Listing extends BaseContentRequest
{
    /**
     * Fetch a listing of ContentType records.
     *
     * @param string  $contentTypeSlug
     * @param string  $order
     * @param integer $page
     * @param array   $taxonomies
     * @param string  $filter
     */
    public function action($contentTypeSlug, $order = null, $page = null, array $taxonomies = null, $filter = null)
    {
        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. Except for
        // a ContentType that has a "grouping taxonomy", as that should override
        // it. That exception state is handled by the query OrderHandler.
        $contenttype = $this->app['storage']->getContentType($contentTypeSlug);
        $contentParameters = [
            'paging'  => true,
            'hydrate' => true,
            'order'   => $order ?: $contenttype['sort'],
            'page'    => $page,
            'filter'  => $filter,
        ];

        // Set the amount of items to show per page
        if (!empty($contenttype['recordsperpage'])) {
            $contentParameters['limit'] = $contenttype['recordsperpage'];
        } else {
            $contentParameters['limit'] = $this->app['config']->get('general/recordsperpage');
        }

        // Filter on taxonomies
        if ($taxonomies !== null) {
            foreach ($taxonomies as $taxonomy => $value) {
                $contentParameters[$taxonomy] = $value;
            }
        }

        return $this->app['storage']->getContent($contentTypeSlug, $contentParameters);
    }
}
