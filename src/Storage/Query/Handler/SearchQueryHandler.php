<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\SearchQuery;
use Bolt\Storage\Query\SearchQueryResultset;
use Bolt\Storage\Query\SearchWeighter;

/**
 *  Handler class to perform search query and then weight the fetched result set.
 */
class SearchQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return SearchQueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $set = new SearchQueryResultset();

        $cleanSearchQuery = $contentQuery->getService('search');
        /** @var SearchQuery $query */
        $query = clone $cleanSearchQuery;

        foreach ($contentQuery->getContentTypes() as $contentType) {
            $repo = $contentQuery->getEntityManager()->getRepository($contentType);
            $query->setQueryBuilder($repo->createQueryBuilder($contentType));
            $query->setContentType($contentType);

            $searchParam = $contentQuery->getParameter('filter');
            $query->setParameters($contentQuery->getParameters());
            $query->setSearch($searchParam);

            $contentQuery->runDirectives($query);

            $result = $repo->queryWith($query);
            if ($result) {
                if (count($result) > 0) {
                    /** @var SearchWeighter $weighter */
                    $weighter = $contentQuery->getService('search_weighter');
                    $weighter->setContentType($contentType);
                    $weighter->setResults($result);
                    $weighter->setSearchWords($query->getSearchWords());

                    $scores = $weighter->weight();
                    $set->add($result, $contentType, $scores);
                } else {
                    $set->add($result, $contentType);
                }
            }
        }

        if ($query->getSingleFetchMode()) {
            return $set->current();
        } else {
            return $set;
        }
    }
}
