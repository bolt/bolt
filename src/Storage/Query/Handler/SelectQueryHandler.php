<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Entity\Content;
use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;
use Bolt\Storage\Query\SelectQuery;
use Bolt\Storage\Repository;

/**
 *  Handler class to perform select query and return a resultset.
 */
class SelectQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return QueryResultset|Content|false
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $set = new QueryResultset();
        /** @var SelectQuery $query */
        $query = $contentQuery->getService('select');
        $query->setSingleFetchMode(false);

        foreach ($contentQuery->getContentTypes() as $contentType) {
            $contentType = str_replace('-', '_', $contentType);
            $repo = $contentQuery->getEntityManager()->getRepository($contentType);
            $query->setQueryBuilder($repo->createQueryBuilder('_' . $contentType));
            $query->setContentType($contentType);

            /** Run the parameters through the whitelister. If we get a false back from this method it's because there
             * is no need to continue with the query.
             */
            $params = $this->whitelistParameters($contentQuery->getParameters(), $repo);
            if (!$params && count($contentQuery->getParameters())) {
                continue;
            }

            /** Continue and run the query add the results to the set */
            $query->setParameters($params);
            $contentQuery->runScopes($query);
            $contentQuery->runDirectives($query);

            $result = $repo->queryWith($query);
            if ($result) {
                $set->setOriginalQuery($contentType, $query->getQueryBuilder());
                $set->add($result, $contentType);
            }
        }

        if ($query->getSingleFetchMode()) {
            if ($set->count() === 0) {
                return false;
            }

            return $set->current();
        }

        return $set;
    }

    /**
     * This block is added to deal with the possibility that a requested filter is not an allowable option on the
     * database table. If the requested field filter is not a valid field on this table then we completely skip
     * the query because no results will be expected if the field does not exist. The exception to this is if the field
     * is part of an OR query then we remove the missing field from the stack but still allow the other fields through.
     *
     * @param array      $queryParams
     * @param Repository $repo
     *
     * @return bool|array $cleanParams
     */
    public function whitelistParameters(array $queryParams, Repository $repo)
    {
        $metadata = $repo->getClassMetadata();
        $allowedParams = array_keys($metadata->getFieldMappings());
        $cleanParams = [];
        foreach ($queryParams as $fieldSelect => $valueSelect) {
            $stack = [];

            if (is_string($valueSelect)) {
                $stack = preg_split('/ *(\|\|\|) */', $fieldSelect);
                $valueStack = preg_split('/ *(\|\|\|) */', $valueSelect);
            }

            if (count($stack) > 1) {
                $allowedKeys = [];
                $allowedVals = [];
                foreach ($stack as $i => $stackItem) {
                    if (in_array($stackItem, $allowedParams)) {
                        $allowedKeys[] = $stackItem;
                        $allowedVals[] = $valueStack[$i];
                    }
                }

                if (!count($allowedKeys)) {
                    return false;
                }
                $allowed = implode(' ||| ', $allowedKeys);
                $cleanParams[$allowed] = implode(' ||| ', $allowedVals);
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
