<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\SearchQueryResultset;

/**
 *  Handler class to perform search query and then weight the fetched resultset.
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

        foreach ($contentQuery->getContentTypes() as $contenttype) {
            $query = $contentQuery->getService('search');
            $repo = $contentQuery->getEntityManager()->getRepository($contenttype);
            $query->setQueryBuilder($repo->createQueryBuilder($contenttype));
            $query->setContentType($contenttype);

            $searchParam = $contentQuery->getParameter('filter');
            $query->setParameters($contentQuery->getParameters());
            $query->setSearch($searchParam);

            $contentQuery->runDirectives($query);

            $result = $repo->queryWith($query);
            if ($result) {
                if (count($result) > 0) {
                    $weighter = $contentQuery->getService('search_weighter');
                    $weighter->setContentType($contenttype);
                    $weighter->setResults($result);
                    $weighter->setSearchWords($query->getSearchWords());

                    $scores = $weighter->weight();
                    $set->add($result, $contenttype, $scores);
                } else {
                    $set->add($result, $contenttype);
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
