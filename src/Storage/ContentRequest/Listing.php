<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Storage\EntityManager;

/**
 * Helper class for ContentType overview listings.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Listing
{
    /** @var EntityManager */
    protected $em;
    /** @var Config */
    protected $config;

    /**
     * Constructor function.
     *
     * @param EntityManager $em
     * @param Config        $config
     */
    public function __construct(EntityManager $em, Config $config)
    {
        $this->em = $em;
        $this->config = $config;
    }

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
        $contenttype = $this->em->getContentType($contentTypeSlug);
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
            $contentParameters['limit'] = $this->config->get('general/recordsperpage');
        }

        // Filter on taxonomies
        if ($taxonomies !== null) {
            foreach ($taxonomies as $taxonomy => $value) {
                $contentParameters[$taxonomy] = $value;
            }
        }

        return $this->em->getContent($contentTypeSlug, $contentParameters);
    }
}
