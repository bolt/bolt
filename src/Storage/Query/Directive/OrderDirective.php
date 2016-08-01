<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Directive to alter query based on 'order' parameter.
 *
 *  eg: 'pages', ['order'=>'-datepublish']
 */
class OrderDirective
{
    /**
     * @param QueryInterface $query
     * @param string         $order
     */
    public function __invoke(QueryInterface $query, $order)
    {
        if ($order === false) {
            return;
        }

        $separatedOrders = $this->getOrderBys($order);
        foreach ($separatedOrders as $order) {
            $order = trim($order);
            if (strpos($order, '-') === 0) {
                $direction = 'DESC';
                $order = substr($order, 1);
            } else {
                $direction = null;
            }
            $query->getQueryBuilder()->addOrderBy($order, $direction);
        }
    }

    /**
     * @param $order
     *
     * @return array
     */
    protected function getOrderBys($order)
    {
        $separatedOrders = [$order];

        if ($this->isMultiOrderQuery($order)) {
            $separatedOrders = explode(',', $order);
        }

        return $separatedOrders;
    }

    /**
     * @param $order
     *
     * @return bool
     */
    protected function isMultiOrderQuery($order)
    {
        return strpos($order, ',') !== false;
    }
}
