<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler to alter query based on 'order' parameter.
 *
 *  eg: 'pages', ['order'=>'-datepublish']
 */
class OrderHandler
{
    /**
     * @param QueryInterface $query
     * @param string         $order
     */
    public function __invoke(QueryInterface $query, $order)
    {
        if (strpos($order, '-') === 0) {
            $direction = 'DESC';
            $order = substr($order, 1);
        } else {
            $direction = null;
        }
        $query->getQueryBuilder()->orderBy($order, $direction);
    }
}
