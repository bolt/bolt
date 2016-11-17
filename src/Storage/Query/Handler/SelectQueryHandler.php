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

            /** Run the parameters through the whitelister. If we get a false back from this method it's because there
             * is no need to continue with the query.
             */
            $params = $this->whitelistParameters($contentQuery->getParameters(), $repo);
            if (!$params) {
                continue;
            }

            /** Continue and run the query add the results to the set */
            $query->setParameters($params);
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

    /** This block is added to deal with the possibility that a requested filter is not an allowable option on the
     * database table. If the requested field filter is not a valid field on this table then we completely skip
     * the query because no results will be expected if the field does not exist. The exception to this is if the field
     * is part of an OR query then we remove the missing field from the stack but still allow the other fields through.
     * @param array $queryParams
     * @param $repo
     * @return bool|array $cleanParams
     */
    public function whitelistParameters(array $queryParams, $repo)
    {
        $metadata = $repo->getClassMetadata();
        $allowedParams = array_keys($metadata->getFieldMappings());
        $cleanParams = [];
        foreach ($queryParams as $fieldSelect => $valueSelect) {
            $stack = preg_split('/ *(\|\|\|) */', $fieldSelect);
            if (count($stack) > 1) {
                $allowed = array_intersect($stack, $allowedParams);
                if (!count($allowed)) {
                    return false;
                }
                $allowed = join(" ||| ", $allowed);
                $cleanParams[$allowed] = $valueSelect;
            } else {
                if (!in_array($fieldSelect, $allowedParams)) {
                    return false;
                }
                $cleanParams[$fieldSelect] = $valueSelect;
            }
        }

        return $cleanParams;
    }
}
