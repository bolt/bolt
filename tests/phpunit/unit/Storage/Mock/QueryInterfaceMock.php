<?php

namespace Bolt\Tests\Storage\Mock;

use Bolt\Storage\Query\QueryInterface;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * QueryInterface mock.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class QueryInterfaceMock implements QueryInterface
{
    /** @var QueryBuilder */
    private $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = new QueryBuilder(new ConnectionMock([], new DriverMock()));
    }

    public function build()
    {
    }

    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}
