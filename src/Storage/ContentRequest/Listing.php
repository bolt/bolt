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
    public function action($contentTypeSlug, ListingOptions $options)
    {
        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. Except for
        // a ContentType that has a "grouping taxonomy", as that should override
        // it. That exception state is handled by the query OrderHandler.
        $contenttype = $this->em->getContentType($contentTypeSlug);
        $contentParameters = [
            'paging'  => true,
            'hydrate' => true,
            'order'   => $options->getOrder() ?: $contenttype['sort'],
            'page'    => $options->getPage(),
            'filter'  => $options->getFilter(),
        ];

        // Set the amount of items to show per page
        if (!empty($contenttype['recordsperpage'])) {
            $contentParameters['limit'] = $contenttype['recordsperpage'];
        } else {
            $contentParameters['limit'] = $this->config->get('general/recordsperpage');
        }

        // Filter on taxonomies
        if ($options->getTaxonomies() !== null) {
            foreach ($options->getTaxonomies() as $taxonomy => $value) {
                $contentParameters[$taxonomy] = $value;
            }
        }

        return $this->em->getContent($contentTypeSlug, $contentParameters);
    }
}
