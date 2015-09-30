<?php

namespace Bolt\Storage\Query;

/**
 * Interface that defines minimum functionality of a Bolt Query class
 *
 * The goal of a query is to store select and filter parameters that can be
 * used to create a relevant SQL expression.
 */
interface QueryInterface
{
    /**
     * Builds the query and returns an instance of QueryBuilder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function build();

    /**
     * Returns the current instance of QueryBuilder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryBuilder();
}
