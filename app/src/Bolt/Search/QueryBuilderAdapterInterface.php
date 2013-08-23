<?php

namespace Bolt\Search;

/**
 * Description of QueryBuilderAdapter
 *
 * @author leon
 */
interface QueryBuilderAdapterInterface
{
    public function modifyQuery($ids);
}
