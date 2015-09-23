<?php

namespace Bolt\Profiler;

use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * DatabaseDataCollector.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class DatabaseDataCollector extends DataCollector
{
    private $logger;

    public function __construct(DebugStack $logger)
    {
        $this->logger = $logger;
    }

    public function getName()
    {
        return 'db';
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = ['queries' => $this->trim($this->logger->queries)];
    }

    public function getQueryCount()
    {
        return count($this->data['queries']);
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += $query['executionMS'];
        }

        return $time;
    }

    private function trim(array $queries)
    {
        $return = [];
        foreach ($queries as $query) {
            // Skip "PRAGMA .." and similar queries by SQLITE.
            if ((strpos($query['sql'], "PRAGMA ") === 0)
                || (strpos($query['sql'], "SELECT DISTINCT k.CONSTRAINT_NAME") === 0)
                || (strpos($query['sql'], "SELECT TABLE_NAME AS Table") === 0)
                || (strpos($query['sql'], "SELECT COLUMN_NAME AS Field") === 0)
            ) {
                continue;
            }
            $return[] = $query;
        }

        return $return;
    }
}
