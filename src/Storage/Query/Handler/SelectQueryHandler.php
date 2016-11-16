<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;

/**
 *  Handler class to perform select query and return a resultset.
 */
class SelectQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return QueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $set = new QueryResultset();

        foreach ($contentQuery->getContentTypes() as $contenttype) {
            $query = $contentQuery->getService('select');
            $repo = $contentQuery->getEntityManager()->getRepository($contenttype);
            $query->setQueryBuilder($repo->createQueryBuilder($contenttype));
            $query->setContentType($contenttype);

            /** This block is added to deal with the possibility that a requested filter is not an allowable option on the
             * database table. If the requested field filter is not a valid field on this table then we completely skip
             * the query because no results will be expected if the field does not exist.
             */
            $metadata = $repo->getClassMetadata();
            $allowedParams = array_keys($metadata->getFieldMappings());
            $queryParams = array_keys($contentQuery->getParameters());
            if (array_diff($queryParams, $allowedParams)) {
                continue;
            }

            $query->setParameters($contentQuery->getParameters());
            $contentQuery->runDirectives($query);

            $result = $repo->queryWith($query);
            if ($result) {
                $set->add($result, $contenttype);
            }
        }

        if ($query->getSingleFetchMode()) {
            return $set->current();
        } else {
            return $set;
        }
    }
}
