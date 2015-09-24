<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\Adapter\PostgresSearch;
use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\SearchQueryResultset;

/**
 *  Handler class to perform a native search where the db adapter supports full=text
 * language searching, thus avoiding the need to weight the results in PHP code.
 */
class NativeSearchHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return SearchQueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $params = $contentQuery->getEntityManager()->createQueryBuilder()->getConnection()->getParams();
        if (strpos($params['driver'], 'postgres') !== false) {
            return $this->postgresSearch($contentQuery);
        } else {
            return call_user_func_array($contentQuery->getHandler('search'), [$contentQuery]);
        }
    }

    public function postgresSearch(ContentQueryParser $contentQuery)
    {
        $set = new SearchQueryResultset();

        foreach ($contentQuery->getContentTypes() as $contenttype) {
            $repo = $contentQuery->getEntityManager()->getRepository($contenttype);
            $query = $repo->createQueryBuilder($contenttype);
            $config = $contentQuery->getService('search_config');
            $search = $contentQuery->getParameter('filter');
            $adapter = new PostgresSearch($query, $config, explode(' ', $search));
            $adapter->setContentType($contenttype);
            $result = $repo->findWith($adapter->getQuery());
            $set->add($result, $contenttype);
        }

        return $set;
    }
}
