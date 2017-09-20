<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Pager\PagerManager;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Query\QueryResultset;

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
    /** @var Query */
    private $query;
    /** @var PagerManager */
    protected $pager;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param Query $query
     * @param Config $config
     * @param PagerManager $pager
     */
    public function __construct(EntityManager $em, Query $query, Config $config, PagerManager $pager)
    {
        $this->em = $em;
        $this->query = $query;
        $this->config = $config;
        $this->pager = $pager;
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
        $contentParameters = array_filter($contentParameters);
        $records = $this->query->getContent($contentTypeSlug, $contentParameters);

        // UGLY HACK! Remove when cutting over to the new storage layer!
        $records = empty($records) ? false : $records;

        if ($records === false && $options->getPage() !== null) {
            $contentParameters['page'] = $options->getPreviousPage();
            $records = $this->query->getContent($contentTypeSlug, $contentParameters);
        }
        $this->runPagerQueries($records);

        return $records;
    }

    /**
     * @param QueryResultset $results
     */
    protected function runPagerQueries($results)
    {
        if (!$results instanceof QueryResultset) {
            return;
        }
        foreach ($results->getOriginalQueries() as $pagerName => $query) {
            $queryCopy = clone $query;
            $queryCopy->setMaxResults(null);
            $queryCopy->setFirstResult(null);

            $totalResults = (int)$queryCopy->execute()->fetchColumn();
            $start = $query->getFirstResult() ? $query->getFirstResult() : 0;
            $currentPage = ($start + $query->getMaxResults()) / $query->getMaxResults();

            $this->pager->createPager($pagerName)
                ->setCount($totalResults)
                ->setTotalpages(ceil($totalResults / $query->getMaxResults()))
                ->setCurrent($currentPage)
                ->setShowingFrom(($start * $query->getMaxResults()) + 1)
                ->setShowingTo((($start - 1) * $query->getMaxResults()) + $results->count());
        }
    }
}
