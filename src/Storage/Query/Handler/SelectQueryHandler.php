<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;

/**
 *  
 */
class SelectQueryHandler
{
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $set = new QueryResultset();

        foreach ($contentQuery->getContentTypes() as $contenttype) {
            $query = $contentQuery->getService('select');
            $repo = $contentQuery->getEntityManager()->getRepository($contenttype);
            $query->setQueryBuilder($repo->createQueryBuilder($contenttype));
            $query->setContentType($contenttype);

            $query->setParameters($contentQuery->getParameters());
            $contentQuery->runDirectives($query);

            $result = $repo->queryWith($query);
            if ($result) {
                $set->add($result, $contenttype);
            }
        }

        return $set;
    }
}
