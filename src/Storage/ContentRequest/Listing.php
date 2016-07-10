<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Storage\Entity\Content;
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
     * @param string         $contentTypeSlug
     * @param ListingOptions $options
     *
     * @return Content|false
     */
    public function action($contentTypeSlug, ListingOptions $options)
    {
        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the ContentType. Except for
        // a ContentType that has a "grouping taxonomy", as that should override
        // it. That exception state is handled by the query OrderHandler.
        $contentType = $this->em->getContentType($contentTypeSlug);
        $contentParameters = [
            'paging'  => true,
            'hydrate' => true,
            'order'   => $options->getOrder() ?: $contentType['sort'],
            'page'    => $options->getPage(),
            'filter'  => $options->getFilter(),
        ];

        // Set the amount of items to show per page
        if (!empty($contentType['recordsperpage'])) {
            $contentParameters['limit'] = $contentType['recordsperpage'];
        } else {
            $contentParameters['limit'] = $this->config->get('general/recordsperpage');
        }

        // Filter on taxonomies
        if ($options->getTaxonomies() !== null) {
            foreach ($options->getTaxonomies() as $taxonomy => $value) {
                $contentParameters[$taxonomy] = $value;
            }
        }

        return $this->getContent($contentTypeSlug, $contentParameters, $options);
    }

    /**
     * Get the content records, and fallback a page if none found.
     *
     * @param string         $contentTypeSlug
     * @param array          $contentParameters
     * @param ListingOptions $options
     *
     * @return Content|false
     */
    protected function getContent($contentTypeSlug, array $contentParameters, ListingOptions $options)
    {
        $records = $this->em->getContent($contentTypeSlug, $contentParameters);

        // UGLY HACK! Remove when cutting over to the new storage layer!
        $records = empty($records) ? false : $records;

        if ($records === false && $options->getPage() !== null) {
            $contentParameters['page'] = $options->getPreviousPage();
            $records = $this->em->getContent($contentTypeSlug, $contentParameters);
        }

        return $records;
    }
}
