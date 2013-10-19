<?php

namespace Bolt\Database;

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

    protected $data;

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
        $this->data = array(
            'queries' => $this->logger->queries,
        );
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
}
