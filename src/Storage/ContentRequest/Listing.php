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
     * @param Query         $query
     * @param Config        $config
     * @param PagerManager  $pager
     */
    public function __construct(EntityManager $em, Query $query, Config $config, PagerManager $pager = null)
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
            'status'  => $options->getStatus(),
        ];

        // If we have a text filter we switch the query into search mode
        if ($options->getFilter()) {
            $textQuery = $contentTypeSlug . '/search';
            $contentParameters['invisible'] = true;
        } else {
            $textQuery = $contentTypeSlug;
        }

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

        return $this->getContent($textQuery, $contentParameters, $options);
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
        if ($records === null && $options->getPage() !== null) {
            $contentParameters['page'] = $options->getPreviousPage();
            $records = $this->query->getContent($contentTypeSlug, $contentParameters);
        }
        $this->runPagerQueries($records);
        if ($options->getGroupSort() && !$options->getOrder()) {
            $records = $this->runGroupSort($records);
        }

        return $records;
    }

    /**
     * @param QueryResultset $results
     */
    protected function runPagerQueries($results)
    {
        if (!$results instanceof QueryResultset || $this->pager === null) {
            return;
        }
        foreach ($results->getOriginalQueries() as $pagerName => $query) {
            $queryCopy = clone $query;
            $queryCopy->select('count(*)');
            $queryCopy->setMaxResults(null);
            $queryCopy->setFirstResult(null);
            $queryCopy->resetQueryPart('orderBy');

            $totalResults = (int) count($queryCopy->execute()->fetchAll());
            $start = $query->getFirstResult() ? $query->getFirstResult() : 0;
            $currentPage = ($start + $query->getMaxResults()) / $query->getMaxResults();

            $this->pager->createPager($pagerName)
                ->setCount($totalResults)
                ->setTotalpages(ceil($totalResults / $query->getMaxResults()))
                ->setCurrent($currentPage)
                ->setShowingFrom($start + 1)
                ->setShowingTo($start + $results->count());
        }
    }

    /**
     * @param $results
     *
     * @return QueryResultset|array
     */
    protected function runGroupSort($results)
    {
        if (!$results instanceof QueryResultset) {
            return $results;
        }
        $grouped = [];
        $resultTaxOrders = [];
        foreach ($results as $result) {
            $taxGroup = null;
            foreach ($result->getTaxonomy() as $taxonomy) {
                if (in_array($taxonomy->getTaxonomytype(), $result->getTaxonomy()->getGroupingTaxonomies())) {
                    $taxGroup = $taxonomy->getSlug();
                    $taxOrder = $taxonomy->getSortorder();
                    $resultTaxOrders[$result->getId()] = $taxOrder;
                }
            }
            if ($taxGroup !== null) {
                $grouped[$taxGroup][] = $result;
            } else {
                $grouped['ungrouped'][] = $result;
            }
        }

        if (!count($grouped)) {
            return $results;
        }
        if (isset($taxGroup) && $taxGroup !== null) {
            foreach ($grouped as &$group) {
                usort($group, function ($a, $b) use ($resultTaxOrders) {
                    $aOrder = isset($resultTaxOrders[$a->getId()]) ? $resultTaxOrders[$a->getId()] : 0;
                    $bOrder = isset($resultTaxOrders[$b->getId()]) ? $resultTaxOrders[$b->getId()] : 0;

                    return $aOrder - $bOrder;
                });
            }
        }

        return call_user_func_array('array_merge', $grouped);
    }
}
