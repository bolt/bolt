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
        // Skip "PRAGMA .." and other similarly queries, that only cause noise in the overview of used queries.
        $cruftarray = [
            'SHOW FULL TABLES WHERE Table_type',
            'PRAGMA ',
            'SELECT DISTINCT k.CONSTRAINT_NAME',
            'SELECT TABLE_NAME AS Table',
            'SELECT COLUMN_NAME AS Field',
            'INNER JOIN information_schema',
            'FROM information_schema',
        ];

        $return = [];
        foreach ($queries as $query) {
            foreach ($cruftarray as $cruft) {
                if (strpos($query['sql'], $cruft) !== false) {
                    continue(2);
                }
            }
            $return[] = $query;
        }

        return $return;
    }
}
